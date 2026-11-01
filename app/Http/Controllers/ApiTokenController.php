<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApiTokenController extends Controller
{
    public function index(): View
    {
        $tokens = auth()->user()->tokens()->latest('id')->get();
        return view('api-tokens', compact('tokens'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => 'required|string|max:255']);
        $token = auth()->user()->createToken($data['name']);
        return back()->with('ok', 'Token dibuat. Salin sekarang, hanya tampil sekali:')
            ->with('plainToken', $token->plainTextToken);
    }

    public function destroy(int $id): RedirectResponse
    {
        auth()->user()->tokens()->where('id', $id)->delete();
        return back()->with('ok', 'Token dihapus.');
    }
}
