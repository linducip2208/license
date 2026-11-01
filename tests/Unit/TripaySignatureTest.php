<?php

namespace Tests\Unit;

use App\Services\TripayService;
use Tests\TestCase;

class TripaySignatureTest extends TestCase
{
    /**
     * Vektor resmi dari dokumentasi Tripay.
     * @see https://tripay.co.id/developer (tab "Buat Signature")
     */
    public function test_make_signature_matches_official_vector(): void
    {
        $this->assertSame(
            '9f167eba844d1fcb369404e2bda53702e2f78f7aa12e91da6715414e65b8c86a',
            TripayService::makeSignature('T0001', 'INV55567', 1500000, 'ytf6ooi2gmlNPfpchd94jDOk8hRWOu')
        );
    }

    public function test_verify_callback_signature(): void
    {
        config(['tripay.private_key' => 'rahasia-dapur']);
        $svc = new TripayService();
        $body = '{"merchant_ref":"WS123","status":"PAID"}';
        $sig = hash_hmac('sha256', $body, 'rahasia-dapur');

        $this->assertTrue($svc->verifyCallbackSignature($body, $sig));
        $this->assertFalse($svc->verifyCallbackSignature($body, 'salah'));
        $this->assertFalse($svc->verifyCallbackSignature($body, null));
    }
}
