<?php

namespace App\Http\Controllers;

use App\Enums\ThreatScenario;
use App\Models\SigningAttempt;
use App\Services\ExperimentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExperimentController extends Controller
{
    public function __construct(private readonly ExperimentService $experimentService) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'configurations' => [
                'configuration_a_otp_only',
                'configuration_b_liveness_only',
                'configuration_c_otp_plus_liveness',
            ],
            'metrics' => [
                'tar',
                'far',
                'attack_success_rate',
                'completion_time_seconds',
                'verification_failure_rate',
            ],
        ]);
    }

    public function run(Request $request): JsonResponse
    {
        $configurations = $request->input('configurations', [
            'configuration_a_otp_only',
            'configuration_b_liveness_only',
            'configuration_c_otp_plus_liveness',
        ]);

        return response()->json([
            'results' => $this->experimentService->run($configurations),
        ]);
    }

    public function storeAttempt(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'document_id' => ['required', 'string', 'max:255'],
            'signer_identifier' => ['required', 'string', 'max:255'],
            'verification_configuration' => ['required', Rule::in([
                'configuration_a_otp_only',
                'configuration_b_liveness_only',
                'configuration_c_otp_plus_liveness',
            ])],
            'threat_scenario' => ['required', Rule::in(array_map(static fn (ThreatScenario $s) => $s->value, ThreatScenario::cases()))],
            'otp_status' => ['required', 'boolean'],
            'liveness_status' => ['required', 'boolean'],
            'signature_status' => ['required', 'boolean'],
            'completion_time_seconds' => ['nullable', 'integer', 'min:0'],
            'failure_reason' => ['nullable', 'string'],
        ]);

        $validated['actor_label'] = $validated['threat_scenario'] === ThreatScenario::LegitimateUser->value
            ? 'legitimate_user'
            : 'attacker';

        $validated['outcome_label'] = $this->isAccepted(
            $validated['verification_configuration'],
            (bool) $validated['otp_status'],
            (bool) $validated['liveness_status'],
            (bool) $validated['signature_status']
        ) ? 'accepted' : 'rejected';

        $attempt = SigningAttempt::create($validated);

        return response()->json([
            'message' => 'Attempt stored.',
            'attempt' => $attempt,
        ], 201);
    }

    public function attempts(Request $request): JsonResponse
    {
        $query = SigningAttempt::query()->latest('id');

        if ($request->filled('verification_configuration')) {
            $query->where('verification_configuration', $request->string('verification_configuration'));
        }

        if ($request->filled('threat_scenario')) {
            $query->where('threat_scenario', $request->string('threat_scenario'));
        }

        return response()->json([
            'attempts' => $query->limit(200)->get(),
        ]);
    }

    private function isAccepted(string $configuration, bool $otp, bool $liveness, bool $signature): bool
    {
        return match ($configuration) {
            'configuration_a_otp_only' => $otp && $signature,
            'configuration_b_liveness_only' => $liveness && $signature,
            'configuration_c_otp_plus_liveness' => $otp && $liveness && $signature,
            default => false,
        };
    }
}
