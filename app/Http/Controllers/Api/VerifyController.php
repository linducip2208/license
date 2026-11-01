<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\License;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerifyController extends Controller
{
    /**
     * Dipanggil aplikasi WASender: verifikasi kode + daftarkan HWID.
     * POST /api/v1/licenses/verify { code, hwid, label?, app_version? }
     */
    public function verify(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => 'required|string|max:32',
            'hwid' => 'required|string|max:128',
            'label' => 'nullable|string|max:255',
            'app_version' => 'nullable|string|max:50',
        ]);

        $code = strtoupper(str_replace('-', '', trim($data['code'])));
        $code = implode('-', str_split($code, 4));
        $license = License::where('code', $code)->first();

        if (!$license) {
            return response()->json(['valid' => false, 'message' => 'Kode lisensi tidak dikenal.']);
        }
        [$ok, $message] = $license->check($data['hwid']);
        if (!$ok) {
            return response()->json([
                'valid' => false,
                'message' => $message,
                'status' => $license->effective_status,
            ]);
        }
        $act = $license->touchActivation($data['hwid'], $data['label'] ?? null);

        return response()->json([
            'valid' => true,
            'message' => 'License is valid.',
            'status' => $license->effective_status,
            'product' => $license->product->slug ?? null,
            'customer' => $license->customer_name,
            'issued_on' => $license->created_at?->toDateString(),
            'expires_on' => $license->expires_on?->toDateString(),
            'activations' => $license->activeActivations()->count() . '/' . $license->max_activations,
            'activation_id' => $act->id,
        ]);
    }

    /** Cabut aktivasi HWID (pindah PC). POST /api/v1/licenses/release { code, hwid } + Sanctum token. */
    public function release(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => 'required|string|max:32',
            'hwid' => 'required|string|max:128',
        ]);
        $code = strtoupper(str_replace('-', '', trim($data['code'])));
        $code = implode('-', str_split($code, 4));
        $license = License::where('code', $code)->first();
        if (!$license) return response()->json(['valid' => false, 'message' => 'Unknown code.']);
        $license->activations()->where('hwid', strtolower(trim($data['hwid'])))->update(['revoked' => true]);
        return response()->json(['valid' => true, 'message' => 'Activation released.']);
    }
}
