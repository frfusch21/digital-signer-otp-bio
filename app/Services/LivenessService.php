<?php

namespace App\Services;

class LivenessService
{
    private const CHALLENGES = ['blink', 'turn_left', 'turn_right', 'smile'];

    public function generateChallenge(): array
    {
        $challenge = self::CHALLENGES[array_rand(self::CHALLENGES)];

        return [
            'challenge' => $challenge,
            'time_limit_seconds' => 10,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    public function verify(array $payload): bool
    {
        return ($payload['continuous_landmarks'] ?? false)
            && ($payload['correct_sequence'] ?? false)
            && ($payload['within_time_limit'] ?? false);
    }
}
