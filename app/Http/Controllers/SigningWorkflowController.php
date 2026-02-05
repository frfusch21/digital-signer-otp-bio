<?php

namespace App\Http\Controllers;

use App\Services\LivenessService;
use App\Services\OtpService;
use App\Services\SignatureService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SigningWorkflowController extends Controller
{
    public function __construct(
        private readonly OtpService $otpService,
        private readonly LivenessService $livenessService,
        private readonly SignatureService $signatureService,
    ) {}

    public function index(Request $request): View|JsonResponse
    {
        $workflow = [
            'Document Preparation',
            'Signature Initiation',
            'OTP Verification',
            'Biometric Liveness',
            'Digital Signature Application',
            'Verification',
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'title' => 'Hybrid PKI + OTP + Biometric Liveness Signing Workflow',
                'workflow_steps' => $workflow,
            ]);
        }

        return view('signing.index', [
            'title' => 'Hybrid PKI + OTP + Biometric Liveness Signing Workflow',
            'workflow' => $workflow,
        ]);
    }

    public function initiate(Request $request): JsonResponse
    {
        return response()->json([
            'document_id' => $request->input('document_id', 'DOC-' . now()->timestamp),
            'otp' => $this->otpService->issueOtp($request->input('channel', 'email')),
            'liveness_challenge' => $this->livenessService->generateChallenge(),
        ]);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $valid = $this->otpService->verify(
            $request->input('expected_otp', ''),
            $request->input('provided_otp', '')
        );

        return response()->json(['otp_valid' => $valid]);
    }

    public function verifyLiveness(Request $request): JsonResponse
    {
        return response()->json([
            'liveness_valid' => $this->livenessService->verify($request->all()),
        ]);
    }

    public function applySignature(Request $request): JsonResponse
    {
        return response()->json([
            'signature' => $this->signatureService->apply(
                $request->input('document_content', ''),
                $request->input('signer_id', 'anonymous')
            ),
        ]);
    }

    public function verifySignature(Request $request): JsonResponse
    {
        return response()->json([
            'signature_valid' => $this->signatureService->verify(
                $request->input('document_content', ''),
                $request->input('document_hash', '')
            ),
        ]);
    }
}
