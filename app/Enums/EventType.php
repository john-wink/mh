<?php

declare(strict_types=1);

namespace App\Enums;

enum EventType: string
{
    case Manual = 'manual';
    case Automatic = 'automatic';
    case System = 'system';
    case ProximityAlert = 'proximity_alert';
    case ZoneEnter = 'zone_enter';
    case ZoneExit = 'zone_exit';
    case StateTransition = 'state_transition';
    case RuleViolation = 'rule_violation';

    /**
     * Get all possible event type values
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get the label for the event type
     */
    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual Event',
            self::Automatic => 'Automatic Event',
            self::System => 'System Event',
            self::ProximityAlert => 'Proximity Alert',
            self::ZoneEnter => 'Zone Enter',
            self::ZoneExit => 'Zone Exit',
            self::StateTransition => 'State Transition',
            self::RuleViolation => 'Rule Violation',
        };
    }

    /**
     * Check if this event type requires manual creation
     */
    public function isManual(): bool
    {
        return $this === self::Manual;
    }

    /**
     * Check if this event type is automatically generated
     */
    public function isAutomatic(): bool
    {
        return in_array($this, [
            self::Automatic,
            self::System,
            self::ProximityAlert,
            self::ZoneEnter,
            self::ZoneExit,
            self::StateTransition,
        ], true);
    }
}
