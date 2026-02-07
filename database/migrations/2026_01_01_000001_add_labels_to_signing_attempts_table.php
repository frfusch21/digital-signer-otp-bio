<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('signing_attempts', function (Blueprint $table) {
            $table->string('actor_label')->default('legitimate_user')->after('failure_reason');
            $table->string('outcome_label')->default('rejected')->after('actor_label');
            $table->index(['verification_configuration', 'threat_scenario'], 'sign_attempt_cfg_scn_idx');
            $table->index(['actor_label', 'outcome_label'], 'sign_attempt_actor_outcome_idx');
        });
    }

    public function down(): void
    {
        Schema::table('signing_attempts', function (Blueprint $table) {
            $table->dropIndex('sign_attempt_cfg_scn_idx');
            $table->dropIndex('sign_attempt_actor_outcome_idx');
            $table->dropColumn(['actor_label', 'outcome_label']);
        });
    }
};
