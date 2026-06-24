<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens;  
    use HasFactory;   
    use HasRoles;      
    use Notifiable;   

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at'     => 'datetime',
            'is_active'         => 'boolean',
            'password'          => 'hashed',  // auto-hashes password on save
        ];
    }

   
    public function incidentUpdates(): HasMany
    {
        return $this->hasMany(IncidentUpdate::class);
    }

    public function issuedCommands(): HasMany
    {
        return $this->hasMany(RobotCommand::class, 'issued_by');
    }

    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    public function recordLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }
}