<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\Security\BlindIndexService;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'phone_bidx',
        'role_id',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'phone',
        'phone_bidx',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'phone' => 'encrypted',
        ];
    }

    /**
     * Motor de Indexação Criptográfica Acionado Automaticamente.
     */
    protected static function booted(): void
    {
        static::saving(function (self $model) {
            if ($model->isDirty('phone') && !empty($model->phone)) {
                $model->phone_bidx = BlindIndexService::make($model->phone);
            }
        });
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function embarcador()
    {
        return $this->hasOne(Embarcador::class);
    }

    public function motorista()
    {
        return $this->hasOne(Motorista::class);
    }
}