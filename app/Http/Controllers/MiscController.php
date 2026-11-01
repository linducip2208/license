<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\License;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MiscController extends Controller
{
    public function logs(Request $request): View
    {
        $logs = ActivityLog::with(['user', 'license'])
            ->latest('id')->paginate(25)->withQueryString();
        return view('logs', compact('logs'));
    }

    public function restore(Request $request): View|RedirectResponse
    {
        $found = null;
        if ($request->filled('q')) {
            $s = trim($request->get('q'));
            $qq = License::with(['product', 'activations'])->where(function ($w) use ($s) {
                $w->where('code', 'like', "%{$s}%")
                    ->orWhere('customer_email', 'like', "%{$s}%");
            });
            if (!auth()->user()->isAdmin()) $qq->where('user_id', auth()->id());
            $found = $qq->first();
            if (!$found) return back()->with('err', 'Lisensi tidak ditemukan.')->withInput();
        }
        return view('restore', compact('found'));
    }

    public function downloads(): View
    {
        return view('downloads');
    }

    public function profile(): View
    {
        return view('profile');
    }

    public function profileUpdate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'password' => 'nullable|string|min:8|confirmed',
        ]);
        $u = auth()->user();
        $u->name = $data['name'];
        if (!empty($data['password'])) $u->password = $data['password'];
        $u->save();
        return back()->with('ok', 'Profil diperbarui.');
    }

    public function support(): View
    {
        return view('support');
    }
}
