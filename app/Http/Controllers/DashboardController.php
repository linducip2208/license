<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\License;
use App\Models\Product;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $q = License::query();
        if (!auth()->user()->isAdmin()) $q->where('user_id', auth()->id());

        $stats = [
            'total' => (clone $q)->count(),
            'active' => (clone $q)->where('status', 'active')->count(),
            'suspended' => (clone $q)->where('status', 'suspended')->count(),
            'expiring' => (clone $q)->whereNotNull('expires_on')
                ->whereDate('expires_on', '<=', now()->addDays(30)->toDateString())
                ->whereDate('expires_on', '>=', now()->toDateString())->count(),
        ];
        $recentLicenses = (clone $q)->with('product')->latest('id')->limit(8)->get();
        $recentLogs = ActivityLog::with('license')->latest('id')->limit(10)->get();
        $products = Product::withCount('licenses')->orderBy('name')->get();

        return view('dashboard', compact('stats', 'recentLicenses', 'recentLogs', 'products'));
    }
}
