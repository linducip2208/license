<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Services\MailConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_can_save_smtp_settings(): void
    {
        $this->actingAs($this->admin())->post(route('settings.update'), [
            'mail_host' => 'smtp.gmail.com',
            'mail_port' => 587,
            'mail_username' => 'akun@gmail.com',
            'mail_password' => 'rahasia-app-password',
            'mail_encryption' => 'tls',
            'mail_from_address' => 'no-reply@pandusolusi.com',
            'mail_from_name' => 'PanduSolusi',
            '_token' => csrf_token(),
        ])->assertRedirect();

        $this->assertSame('smtp.gmail.com', Setting::get('mail_host'));
        $this->assertSame('akun@gmail.com', Setting::get('mail_username'));

        // Password tersimpan terenkripsi, bukan plaintext.
        $raw = Setting::where('key', 'mail_password')->first()->value;
        $this->assertNotSame('rahasia-app-password', $raw);
        $this->assertSame('rahasia-app-password', Crypt::decryptString($raw));
    }

    public function test_password_kept_when_left_blank(): void
    {
        Setting::setEncrypted('mail_password', 'lama');
        $this->actingAs($this->admin())->post(route('settings.update'), [
            'mail_host' => 'smtp.gmail.com',
            'mail_port' => 587,
            'mail_username' => 'akun@gmail.com',
            'mail_password' => '',
            'mail_from_address' => 'no-reply@pandusolusi.com',
            '_token' => csrf_token(),
        ])->assertRedirect();

        $this->assertSame('lama', Setting::getDecrypted('mail_password'));
    }

    public function test_settings_page_never_shows_password(): void
    {
        Setting::setEncrypted('mail_password', 'jangan-tampil');
        $html = $this->actingAs($this->admin())->get(route('settings.index'))->assertOk()->content();
        $this->assertStringNotContainsString('jangan-tampil', $html);
    }

    public function test_mail_config_applies_from_settings(): void
    {
        Setting::set('mail_host', 'smtp.gmail.com');
        Setting::set('mail_port', '587');
        Setting::set('mail_username', 'akun@gmail.com');
        Setting::setEncrypted('mail_password', 'pw123');
        Setting::set('mail_encryption', 'tls');

        $this->assertTrue(MailConfig::applyFromSettings());
        $this->assertSame('smtp.gmail.com', config('mail.mailers.smtp.host'));
        $this->assertSame('pw123', config('mail.mailers.smtp.password'));
    }

    public function test_test_email_endpoint_sends(): void
    {
        Mail::fake();
        $this->actingAs($this->admin())->post(route('settings.test'), [
            'email' => 'tes@example.com',
            '_token' => csrf_token(),
        ])->assertRedirect()->assertSessionHas('ok');
    }

    public function test_non_admin_cannot_access_settings(): void
    {
        $reseller = User::factory()->create(['role' => 'reseller']);
        $this->actingAs($reseller)->get(route('settings.index'))->assertForbidden();
    }
}
