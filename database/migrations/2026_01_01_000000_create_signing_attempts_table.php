<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('signing_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('document_id')->index();
            $table->string('signer_identifier')->index();
            $table->string('verification_configuration');
            $table->boolean('otp_status')->default(false);
            $table->boolean('liveness_status')->default(false);
            $table->boolean('signature_status')->default(false);
            $table->string('threat_scenario')->nullable();
            $table->unsignedInteger('completion_time_seconds')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signing_attempts');
    }
};
