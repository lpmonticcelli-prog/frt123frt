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
        // Logs de Blindagem Jurídica (Clickwrap)
        'termo_versao',
        'termo_ip',
        'termo_user_agent',
        'termo_aceite_em',
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
            'termo_aceite_em' => 'datetime', // Converte automaticamente o registro para objeto Carbon
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