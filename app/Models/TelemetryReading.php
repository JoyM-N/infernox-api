<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelemetryReading extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'robot_id',
        'lat',
        'lng',
        'battery_level',
        'temperature',
        'smoke_level',
        'fire_detected',
        'image_path',
        'raw_payload',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [

            'raw_payload'   => 'array',
            'fire_detected' => 'boolean',
            'battery_level' => 'integer',
            'temperature'   => 'float',
            'smoke_level'   => 'float',
            'lat'           => 'decimal:7',
            'lng'           => 'decimal:7',
            'recorded_at'   => 'datetime',
        ];
    }

    public function robot(): BelongsTo
    {
        return $this->belongsTo(Robot::class);
    }


    public function isAnomalous(): bool
    {
        return $this->fire_detected
            || $this->temperature > config('infernox.thresholds.fire_temperature')
            || $this->smoke_level  > config('infernox.thresholds.smoke_ppm');
    }

    public function hasLocation(): bool
    {
        return $this->lat !== null && $this->lng !== null;
    }
}