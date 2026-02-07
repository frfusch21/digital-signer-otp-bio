<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SigningAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'signer_identifier',
        'verification_configuration',
        'otp_status',
        'liveness_status',
        'signature_status',
        'threat_scenario',
        'completion_time_seconds',
        'failure_reason',
        'actor_label',
        'outcome_label',
    ];
}
