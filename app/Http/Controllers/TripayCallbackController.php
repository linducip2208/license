<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderFulfiller;
use App\Services\TripayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Menerima notifikasi status transaksi dari server Tripay.
 * POST /api/tripay/callback  (tanpa auth, diamankan via signature)
 */
class TripayCallbackController extends Controller
{
    public function handle(Request $request, TripayService $tripay, OrderFulfiller $fulfiller): JsonResponse
    {
        $raw = $request->getContent();

        if (!$tripay->verifyCallbackSignature($raw, $request->header('X-Callback-Signature'))) {
            return response()->json(['success' => false, 'message' => 'Invalid signature'], 403);
        }

        if (($request->header('X-Callback-Event') ?? '') !== 'payment_status') {
            return response()->json(['success' => true]);
        }

        $data = $request->json()->all();
        $order = Order::where('merchant_ref', $data['merchant_ref'] ?? '')->first();
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        $status = strtoupper($data['status'] ?? '');
        if ($status === 'PAID') {
            $fulfiller->fulfill($order, $data); // idempotent + validasi nominal
        } elseif (in_array($status, ['EXPIRED', 'FAILED']) && !$order->isPaid()) {
            $order->update([
                'status' => $status,
                'payload' => array_merge($order->payload ?? [], ['callback' => $data]),
            ]);
        }

        return response()->json(['success' => true]);
    }
}
