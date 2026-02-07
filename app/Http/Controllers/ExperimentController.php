<?php

namespace App\Http\Controllers;

use App\Services\ExperimentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
}
