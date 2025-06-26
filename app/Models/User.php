<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'is_verified',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_verified' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    // Role constants
    const ROLE_SENDER = 'sender';
    const ROLE_RECEIVER = 'receiver';
    const ROLE_AGENT = 'agent';
    const ROLE_DRIVER = 'driver';
    const ROLE_ADMIN = 'admin';

    // Relationships
    public function sentShipments()
    {
        return $this->hasMany(Shipment::class, 'sender_id');
    }

    public function receivedShipments()
    {
        return $this->hasMany(Shipment::class, 'receiver_id');
    }

    public function agentShipments()
    {
        return $this->hasMany(Shipment::class, 'agent_id');
    }

    public function driverShipments()
    {
        return $this->hasMany(Shipment::class, 'driver_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function vehicle()
    {
        return $this->hasOne(Vehicle::class);
    }

    public function driverAssignments()
    {
        return $this->hasMany(DriverAssignment::class);
    }

    public function businessCourierRule()
    {
        return $this->hasOne(BusinessCourierRule::class, 'seller_id');
    }

    // Role checking methods
    public function isSender(): bool
    {
        return $this->role === self::ROLE_SENDER;
    }

    public function isReceiver(): bool
    {
        return $this->role === self::ROLE_RECEIVER;
    }

    public function isAgent(): bool
    {
        return $this->role === self::ROLE_AGENT;
    }

    public function isDriver(): bool
    {
        return $this->role === self::ROLE_DRIVER;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }
}
