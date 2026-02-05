<?php

namespace App\Services;

class OtpService
{
    public function issueOtp(string $channel): array
    {
        $otp = (string) random_int(100000, 999999);

        return [
            'channel' => $channel,
            'otp_preview' => substr($otp, 0, 2) . '****',
            'issued_at' => now()->toIso8601String(),
            'expires_in_seconds' => 120,
            'otp' => $otp,
        ];
    }

    public function verify(string $expectedOtp, string $providedOtp): bool
    {
        return hash_equals($expectedOtp, $providedOtp);
    }
}
