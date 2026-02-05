<?php

namespace App\Services;

class SignatureService
{
    public function apply(string $documentContent, string $signerId): array
    {
        $digest = hash('sha256', $documentContent . '|' . $signerId . '|' . now()->timestamp);

        return [
            'signature_id' => 'SIG-' . strtoupper(substr($digest, 0, 12)),
            'algorithm' => 'RSA-PSS (simulated)',
            'document_hash' => hash('sha256', $documentContent),
            'signed_at' => now()->toIso8601String(),
        ];
    }

    public function verify(string $documentContent, string $documentHash): bool
    {
        return hash_equals(hash('sha256', $documentContent), $documentHash);
    }
}
