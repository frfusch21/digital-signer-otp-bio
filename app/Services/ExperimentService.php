<?php

namespace App\Services;

use App\Enums\ThreatScenario;

class ExperimentService
{
    public function run(array $configurations): array
    {
        $scenarios = ThreatScenario::cases();

        $results = [];
        foreach ($configurations as $configuration) {
            foreach ($scenarios as $scenario) {
                $results[] = [
                    'configuration' => $configuration,
                    'scenario' => $scenario->value,
                    'tar' => $this->metric($configuration, $scenario, 'tar'),
                    'far' => $this->metric($configuration, $scenario, 'far'),
                    'attack_success_rate' => $this->metric($configuration, $scenario, 'attack_success_rate'),
                    'completion_time_seconds' => $this->metric($configuration, $scenario, 'completion_time_seconds'),
                    'verification_failure_rate' => $this->metric($configuration, $scenario, 'verification_failure_rate'),
                ];
            }
        }

        return $results;
    }

    private function metric(string $configuration, ThreatScenario $scenario, string $metric): float
    {
        $seed = crc32($configuration . $scenario->value . $metric);
        return round(($seed % 1000) / 10, 2);
    }
}
