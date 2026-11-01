<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\License;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LicenseController extends Controller
{
    private function scope($q)
    {
        if (!auth()->user()->isAdmin()) {
            $q->where('licenses.user_id', auth()->id());
        }
        return $q;
    }

    private function log(?int $licenseId, string $action, ?string $detail = null): void
    {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'license_id' => $licenseId,
            'action' => $action,
            'detail' => $detail,
            'ip' => request()->ip(),
        ]);
    }

    public function index(Request $request): View
    {
        $q = License::with(['product', 'owner'])->withCount([
            'activations as active_count' => fn ($a) => $a->where('revoked', false),
        ]);

        $this->scope($q);

        if ($s = trim((string) $request->get('q', ''))) {
            $q->where(function ($w) use ($s) {
                $w->where('code', 'like', "%{$s}%")
                    ->orWhere('customer_name', 'like', "%{$s}%")
                    ->orWhere('customer_email', 'like', "%{$s}%");
            });
        }
        if ($request->filled('product')) {
            $q->where('product_id', $request->get('product'));
        }
        if ($request->filled('from')) {
            $q->whereDate('expires_on', '>=', $request->get('from'));
        }
        if ($request->filled('to')) {
            $q->whereDate('expires_on', '<=', $request->get('to'));
        }
        if ($request->filled('status') && $request->get('status') !== 'all') {
            $st = $request->get('status');
            if ($st === 'expired') {
                $q->whereNotNull('expires_on')->whereDate('expires_on', '<', now()->toDateString());
            } else {
                $q->where('status', $st);
            }
        }

        $licenses = $q->latest('id')->paginate(20)->withQueryString();
        $products = Product::where('is_active', true)->orderBy('name')->get();
        $total = (clone $q)->count();

        return view('licenses.index', compact('licenses', 'products', 'total'));
    }

    public function create(): View
    {
        $products = Product::where('is_active', true)->orderBy('name')->get();
        return view('licenses.create', compact('products'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'product_id' => 'required|exists:products,id',
            'max_activations' => 'required|integer|min:1|max:100',
            'expires_on' => 'nullable|date|after:today',
            'notes' => 'nullable|string|max:2000',
        ]);
        $data['code'] = License::generateCode();
        $data['user_id'] = auth()->id();
        $data['status'] = 'active';
        $license = License::create($data);
        $this->log($license->id, 'created', 'Code ' . $license->code);
        return redirect()->route('licenses.show', $license)->with('ok', 'License ' . $license->code . ' dibuat.');
    }

    public function show(License $license): View
    {
        $this->authorizeView($license);
        $license->load(['product', 'owner', 'activations']);
        return view('licenses.show', compact('license'));
    }

    public function edit(License $license): View
    {
        $this->authorizeView($license);
        $products = Product::where('is_active', true)->orderBy('name')->get();
        return view('licenses.edit', compact('license', 'products'));
    }

    public function update(Request $request, License $license): RedirectResponse
    {
        $this->authorizeView($license);
        $data = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'product_id' => 'required|exists:products,id',
            'max_activations' => 'required|integer|min:1|max:100',
            'expires_on' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
        ]);
        $license->update($data);
        $this->log($license->id, 'updated', 'Data lisensi diubah.');
        return redirect()->route('licenses.show', $license)->with('ok', 'Lisensi diperbarui.');
    }

    public function destroy(License $license): RedirectResponse
    {
        $this->authorizeView($license);
        $code = $license->code;
        $this->log($license->id, 'deleted', 'Code ' . $code);
        $license->delete();
        return redirect()->route('licenses.index')->with('ok', 'Lisensi ' . $code . ' dihapus.');
    }

    public function suspend(License $license): RedirectResponse
    {
        $this->authorizeView($license);
        $license->update(['status' => 'suspended']);
        $this->log($license->id, 'suspended', 'Lisensi di-suspend.');
        return back()->with('ok', 'Lisensi di-suspend.');
    }

    public function activate(License $license): RedirectResponse
    {
        $this->authorizeView($license);
        $license->update(['status' => 'active']);
        $this->log($license->id, 'activated', 'Lisensi diaktifkan.');
        return back()->with('ok', 'Lisensi diaktifkan.');
    }

    public function regenerate(License $license): RedirectResponse
    {
        $this->authorizeView($license);
        $old = $license->code;
        $license->update(['code' => License::generateCode()]);
        $license->activations()->update(['revoked' => true]);
        $this->log($license->id, 'regenerated', $old . ' → ' . $license->code . ' (aktivasi lama dicabut)');
        return back()->with('ok', 'Kode baru: ' . $license->code);
    }

    public function extend(Request $request, License $license): RedirectResponse
    {
        $this->authorizeView($license);
        $data = $request->validate(['days' => 'required|integer|min:1|max:3650']);
        $days = (int) $data['days'];
        $base = ($license->expires_on && $license->expires_on->isFuture())
            ? $license->expires_on->copy()
            : now();
        $newDate = $base->addDays($days)->toDateString();
        $license->update(['expires_on' => $newDate, 'status' => 'active']);
        $this->log($license->id, 'extended', '+' . $days . ' hari → ' . $newDate);
        return back()->with('ok', 'Masa berlaku diperpanjang ' . $days . ' hari.');
    }

    public function revokeActivation(Request $request, License $license, int $activation): RedirectResponse
    {
        $this->authorizeView($license);
        $act = $license->activations()->findOrFail($activation);
        $act->update(['revoked' => true]);
        $this->log($license->id, 'activation_revoked', 'HWID ' . $act->hwid);
        return back()->with('ok', 'Aktivasi dicabut.');
    }

    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:licenses,id',
            'action' => 'required|in:delete,suspend,activate',
        ]);
        $q = License::whereIn('id', $data['ids']);
        $this->scope($q);
        $licenses = $q->get();
        foreach ($licenses as $lic) {
            if ($data['action'] === 'delete') {
                $this->log($lic->id, 'deleted', 'Bulk delete ' . $lic->code);
                $lic->delete();
            } else {
                $lic->update(['status' => $data['action'] === 'suspend' ? 'suspended' : 'active']);
                $this->log($lic->id, $data['action'] . 'd', 'Bulk ' . $data['action']);
            }
        }
        return back()->with('ok', count($licenses) . ' lisensi diproses.');
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $rows = $this->filteredForExport($request);
        $name = 'licenses-' . now()->format('Ymd-His') . '.csv';
        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Code', 'Customer', 'Email', 'Product', 'Status', 'Activations', 'Expires On', 'Created']);
            foreach ($rows as $l) {
                fputcsv($out, [
                    $l->code, $l->customer_name, $l->customer_email, $l->product->name ?? '-',
                    $l->effective_status, $l->active_count . '/' . $l->max_activations,
                    $l->expires_on?->toDateString() ?? '-', $l->created_at->toDateTimeString(),
                ]);
            }
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv']);
    }

    public function exportExcel(Request $request)
    {
        $rows = $this->filteredForExport($request);
        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $ss->getActiveSheet();
        $ws->fromArray(['Code', 'Customer', 'Email', 'Product', 'Status', 'Activations', 'Expires On', 'Created'], null, 'A1');
        $r = 2;
        foreach ($rows as $l) {
            $ws->fromArray([
                $l->code, $l->customer_name, $l->customer_email, $l->product->name ?? '-',
                $l->effective_status, $l->active_count . '/' . $l->max_activations,
                $l->expires_on?->toDateString() ?? '-', $l->created_at->toDateTimeString(),
            ], null, 'A' . $r++);
        }
        foreach (range('A', 'H') as $c) $ws->getColumnDimension($c)->setAutoSize(true);
        $name = 'licenses-' . now()->format('Ymd-His') . '.xlsx';
        return response()->streamDownload(function () use ($ss) {
            (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss))->save('php://output');
        }, $name, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    private function filteredForExport(Request $request)
    {
        $q = License::with(['product'])->withCount([
            'activations as active_count' => fn ($a) => $a->where('revoked', false),
        ]);
        $this->scope($q);
        if ($s = trim((string) $request->get('q', ''))) {
            $q->where(function ($w) use ($s) {
                $w->where('code', 'like', "%{$s}%")
                    ->orWhere('customer_name', 'like', "%{$s}%")
                    ->orWhere('customer_email', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status') && $request->get('status') !== 'all') {
            $q->where('status', $request->get('status'));
        }
        return $q->latest('id')->limit(5000)->get();
    }

    private function authorizeView(License $license): void
    {
        if (!auth()->user()->isAdmin() && $license->user_id !== auth()->id()) {
            abort(403);
        }
    }
}
