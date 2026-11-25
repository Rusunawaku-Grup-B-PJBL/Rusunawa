<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_PENGHUNI = 'penghuni';
    public const ROLE_ADMIN = 'admin';

    // Catatan: role tidak pernah diambil dari input pengguna (lihat AuthController).
    protected $fillable = ['name', 'nim', 'email', 'phone', 'password', 'role'];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function damageReports(): HasMany
    {
        return $this->hasMany(DamageReport::class);
    }

    public function complaintTickets(): HasMany
    {
        return $this->hasMany(ComplaintTicket::class);
    }

    public function readAnnouncements(): BelongsToMany
    {
        return $this->belongsToMany(Announcement::class, 'announcement_reads')
            ->withPivot('read_at');
    }

    public function supportConversation(): HasOne
    {
        return $this->hasOne(SupportConversation::class);
    }
}
