<?php

namespace App\Services;

use App\Enums\ThreatScenario;
use App\Models\SigningAttempt;

class ExperimentService
{
    public function run(array $configurations): array
    {
        $scenarios = ThreatScenario::cases();

        $attempts = SigningAttempt::query()
            ->whereIn('verification_configuration', $configurations)
            ->get()
            ->groupBy(fn (SigningAttempt $attempt) => $attempt->verification_configuration . '|' . $attempt->threat_scenario);

        $results = [];

        foreach ($configurations as $configuration) {
            foreach ($scenarios as $scenario) {
                $key = $configuration . '|' . $scenario->value;
                $group = $attempts->get($key, collect());

                $tp = $group->where('actor_label', 'legitimate_user')->where('outcome_label', 'accepted')->count();
                $fn = $group->where('actor_label', 'legitimate_user')->where('outcome_label', 'rejected')->count();
                $fp = $group->where('actor_label', 'attacker')->where('outcome_label', 'accepted')->count();
                $tn = $group->where('actor_label', 'attacker')->where('outcome_label', 'rejected')->count();

                $legitimateTotal = $tp + $fn;
                $attackTotal = $fp + $tn;

                $results[] = [
                    'configuration' => $configuration,
                    'scenario' => $scenario->value,
                    'attempt_count' => $group->count(),
                    'tp' => $tp,
                    'fn' => $fn,
                    'fp' => $fp,
                    'tn' => $tn,
                    'tar' => $this->percentage($tp, $legitimateTotal),
                    'far' => $this->percentage($fp, $attackTotal),
                    'attack_success_rate' => $this->percentage($fp, $attackTotal),
                    'completion_time_seconds' => $group->avg('completion_time_seconds') !== null
                        ? round((float) $group->avg('completion_time_seconds'), 2)
                        : null,
                    'verification_failure_rate' => $this->percentage($fn, $legitimateTotal),
                ];
            }
        }

        return $results;
    }

    private function percentage(int $numerator, int $denominator): ?float
    {
        if ($denominator === 0) {
            return null;
        }

        return round(($numerator / $denominator) * 100, 2);
    }
}
