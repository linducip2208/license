<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderFulfiller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/** Admin: daftar order masuk + tombol pelunasan manual (QRIS statis). */
class OrderAdminController extends Controller
{
    public function index(): View
    {
        $orders = Order::with(['product', 'license'])->latest('id')->paginate(20);

        return view('orders.index', compact('orders'));
    }

    public function fulfill(Order $order, OrderFulfiller $fulfiller): RedirectResponse
    {
        $fulfiller->fulfillManually($order);

        return back()->with('ok', 'Order ' . $order->merchant_ref . ' dilunasi. Lisensi: ' . $order->fresh('license')->license->code);
    }
}
