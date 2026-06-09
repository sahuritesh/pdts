<?php

namespace App\Services;

class DelayRegisterService
{
    protected DelayCalculationService $delayCalculation;
    protected DelaySeverityService $delaySeverity;
    protected EscalationService $escalation;

    public function __construct(
        DelayCalculationService $delayCalculation,
        DelaySeverityService $delaySeverity,
        EscalationService $escalation
    ) {
        $this->delayCalculation = $delayCalculation;
        $this->delaySeverity = $delaySeverity;
        $this->escalation = $escalation;
    }

    /**
     * Apply delay days, severity, alert level, and escalation to register payload.
     */
    public function applyAutoCalculations(array $data): array
    {
        $start = $data['delay_start_date'] ?? null;
        $end = $data['delay_end_date'] ?? null;
        $licensing = !empty($data['licensing_openings_affected']);

        $delayDays = $this->delayCalculation->calculateDelayDays($start, $end);
        $severity = $this->delaySeverity->resolveSeverity($delayDays, $licensing);
        $alertLevel = $this->delaySeverity->severityToAlertLevel($severity);
        $escalationLevel = $this->escalation->resolveEscalationLevel($severity, $alertLevel);

        $data['delay_days'] = $delayDays;
        $data['severity'] = $severity;
        $data['alert_level'] = $alertLevel;
        $data['escalation_level'] = $escalationLevel;
        $data['licensing_openings_affected'] = $licensing ? 1 : 0;

        return $data;
    }
}
