<?php

namespace App\Models;

use App\Enums\CommandStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RobotCommand extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'robot_id',
        'issued_by',
        'incident_id',
        'command_type',
        'payload',
        'status',
        'issued_at',
        'acknowledged_at',
        'executed_at',
    ];

    protected function casts(): array
    {
        return [
            // payload is JSON — auto converts to PHP array
            'payload'          => 'array',
            'status'           => CommandStatus::class,
            'issued_at'        => 'datetime',
            'acknowledged_at'  => 'datetime',
            'executed_at'      => 'datetime',
        ];
    }

    public function robot(): BelongsTo
    {
        return $this->belongsTo(Robot::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }


    public function isPending(): bool
    {
        return $this->status === CommandStatus::PENDING;
    }

    public function isExpired(): bool
    {
        return $this->status === CommandStatus::EXPIRED;
    }

    public function hasBeenExecuted(): bool
    {
        return $this->status === CommandStatus::EXECUTED;
    }
}