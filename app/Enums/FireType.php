<?php

namespace App\Enums;

enum FireType: string
{
    case CLASS_A = 'class_a';
    case CLASS_B = 'class_b';
    case CLASS_C = 'class_c';
    case CLASS_D = 'class_d';
    case CLASS_F = 'class_f';
    case UNKNOWN = 'unknown';

    public function label(): string
    {
        return match($this) {
            self::CLASS_A => 'Class A — Ordinary Combustibles',
            self::CLASS_B => 'Class B — Flammable Liquids',
            self::CLASS_C => 'Class C — Electrical Equipment',
            self::CLASS_D => 'Class D — Flammable Metals',
            self::CLASS_F => 'Class F — Cooking Oils',
            self::UNKNOWN => 'Unknown',
        };
    }

    public function recommendedExtinguisher(): string
    {
        return match($this) {
            self::CLASS_A => 'Water, Foam, or Dry Powder extinguisher',
            self::CLASS_B => 'Foam, CO2, or Dry Powder extinguisher — DO NOT use water',
            self::CLASS_C => 'CO2 extinguisher ONLY — NEVER use water on electrical fires',
            self::CLASS_D => 'Specialist Dry Powder extinguisher only',
            self::CLASS_F => 'Wet Chemical extinguisher only',
            self::UNKNOWN => 'Assess fire type before selecting extinguisher',
        };
    }

    public function dangerLevel(): string
    {
        return match($this) {
            self::CLASS_C => 'HIGH — Electrical fires can cause electrocution',
            self::CLASS_B => 'HIGH — Flammable liquids spread rapidly',
            self::CLASS_D => 'EXTREME — Metal fires are extremely dangerous',
            self::CLASS_F => 'HIGH — Cooking oil fires explode with water',
            self::CLASS_A => 'MODERATE — Standard fire behavior',
            self::UNKNOWN => 'UNKNOWN — Exercise maximum caution',
        };
    }
}