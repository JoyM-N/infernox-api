<?php

namespace App\Models;

use App\Enums\IncidentStatus;
use App\Enums\IncidentSeverity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Incident extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'robot_id',
        'status',
        'severity',
        'lat',
        'lng',
        'peak_temperature',
        'peak_smoke_level',
        'detected_at',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'status'            => IncidentStatus::class,
            'severity'          => IncidentSeverity::class,
            'lat'               => 'decimal:7',
            'lng'               => 'decimal:7',
            'peak_temperature'  => 'float',
            'peak_smoke_level'  => 'float',
            'detected_at'       => 'datetime',
            'resolved_at'       => 'datetime',
        ];
    }

    public function robot(): BelongsTo
    {
        return $this->belongsTo(Robot::class);
    }

    public function updates(): HasMany
    {
        return $this->hasMany(IncidentUpdate::class);
    }

    public function commands(): HasMany
    {
        return $this->hasMany(RobotCommand::class);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            'open',
            'investigating',
            'suppressing',
        ]);
    }


    public function isActive(): bool
    {
        return in_array($this->status->value, [
            'open',
            'investigating',
            'suppressing',
        ]);
    }

    public function isResolved(): bool
    {
        return in_array($this->status->value, [
            'resolved',
            'false_alarm',
        ]);
    }

 
    public function updatePeakReadings(TelemetryReading $reading): void
    {
        $this->update([
            'peak_temperature' => max(
                $this->peak_temperature ?? 0,
                $reading->temperature ?? 0
            ),
            'peak_smoke_level' => max(
                $this->peak_smoke_level ?? 0,
                $reading->smoke_level ?? 0
            ),
        ]);
    }
}