<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\MailConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        return view('settings.index', [
            'mail_host' => Setting::get('mail_host', ''),
            'mail_port' => Setting::get('mail_port', '587'),
            'mail_username' => Setting::get('mail_username', ''),
            'mail_encryption' => Setting::get('mail_encryption', 'tls'),
            'mail_from_address' => Setting::get('mail_from_address', ''),
            'mail_from_name' => Setting::get('mail_from_name', ''),
            'has_password' => Setting::has('mail_password'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'mail_host' => 'required|string|max:255',
            'mail_port' => 'required|integer|min:1|max:65535',
            'mail_username' => 'required|string|max:255',
            'mail_password' => 'nullable|string|max:1000',
            'mail_encryption' => 'nullable|in:tls,ssl,none',
            'mail_from_address' => 'required|email|max:255',
            'mail_from_name' => 'nullable|string|max:255',
        ]);

        Setting::set('mail_host', $data['mail_host']);
        Setting::set('mail_port', (string) $data['mail_port']);
        Setting::set('mail_username', $data['mail_username']);
        Setting::set('mail_encryption', $data['mail_encryption'] ?? 'tls');
        Setting::set('mail_from_address', $data['mail_from_address']);
        Setting::set('mail_from_name', $data['mail_from_name'] ?? '');

        // Password hanya diubah bila diisi; tidak pernah ditampilkan balik.
        if (!empty($data['mail_password'])) {
            Setting::setEncrypted('mail_password', $data['mail_password']);
        }

        return back()->with('ok', 'Pengaturan email disimpan. Password tersimpan terenkripsi.');
    }

    public function test(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => 'required|email|max:255']);

        MailConfig::applyFromSettings();
        try {
            Mail::raw('Tes pengiriman email dari panel lisensi. Bila pesan ini masuk, SMTP sudah benar.', function ($m) use ($data) {
                $m->to($data['email'])->subject('Tes Email Panel Lisensi');
            });
        } catch (\Throwable $e) {
            return back()->withErrors(['email' => 'Gagal mengirim: ' . $e->getMessage()]);
        }

        return back()->with('ok', 'Email tes terkirim ke ' . $data['email'] . '. Cek inbox/spam.');
    }
}
