<?php

namespace Tests\Unit;

use App\Modules\Auth\Totp;
use PHPUnit\Framework\TestCase;

class TotpTest extends TestCase
{
    public function test_rfc6238_vector(): void
    {
        // RFC 6238 Appendix B (SHA1), secret "12345678901234567890", T=59 → 94287082 (6 số cuối: 287082)
        $secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';
        $this->assertSame('287082', Totp::code($secret, 59));
    }

    public function test_verify_accepts_adjacent_window_only(): void
    {
        $secret = Totp::generateSecret();
        $this->assertTrue(Totp::verify($secret, Totp::code($secret, time() - 30)));
        $this->assertFalse(Totp::verify($secret, Totp::code($secret, time() - 300)));
    }
}
