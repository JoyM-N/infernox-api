<?php

namespace App\Models;

use App\Enums\RobotStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

class Robot extends Model
{
    use HasApiTokens; 
    use HasFactory;
    use HasUuids;     
    use SoftDeletes;  
    protected $fillable = [
        'serial_number',
        'name',
        'model',
        'status',
        'last_known_lat',
        'last_known_lng',
        'battery_level',
        'last_seen_at',
        'commissioned_at',
    ];

    protected $hidden = [
    ];

    protected function casts(): array
    {
        return [
            'status'           => RobotStatus::class, 
            'last_known_lat'   => 'decimal:7',
            'last_known_lng'   => 'decimal:7',
            'battery_level'    => 'integer',
            'last_seen_at'     => 'datetime',
            'commissioned_at'  => 'datetime',
        ];
    }

  

    public function telemetryReadings(): HasMany
    {
        return $this->hasMany(TelemetryReading::class);
    }

    public function latestTelemetry(): HasOne
    {
        return $this->hasOne(TelemetryReading::class)
                    ->latestOfMany('recorded_at');
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    public function activeIncident(): HasOne
    {
        return $this->hasOne(Incident::class)
                    ->whereIn('status', ['open', 'investigating', 'suppressing'])
                    ->latestOfMany('detected_at');
    }

    public function commands(): HasMany
    {
        return $this->hasMany(RobotCommand::class);
    }

    public function scopeOnline($query)
    {
        return $query->where('status', RobotStatus::ONLINE)
                     ->where('last_seen_at', '>=', now()->subSeconds(60));
    }

    public function scopeLowBattery($query)
    {
        return $query->where(
            'battery_level', '<=',
            config('infernox.thresholds.battery_low')
        );
    }

    public function scopeWithActiveIncident($query)
    {
        return $query->whereHas('incidents', fn($q) =>
            $q->whereIn('status', ['open', 'investigating', 'suppressing'])
        );
    }

    public function isOnline(): bool
    {
        return $this->status->isOperational()
            && $this->last_seen_at?->isAfter(now()->subSeconds(60));
    }

    public function isAvailableForCommand(): bool
    {
        return $this->isOnline()
            && $this->status !== RobotStatus::ERROR
            && $this->battery_level > config('infernox.thresholds.battery_low');
    }

    public function markAsOnline(): void
    {
        $this->update([
            'status'       => RobotStatus::ONLINE,
            'last_seen_at' => now(),
        ]);
    }

    public function markAsOffline(): void
    {
        $this->update(['status' => RobotStatus::OFFLINE]);
    }
}