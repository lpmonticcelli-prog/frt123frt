<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * Atributos que podem ser preenchidos via User::create()
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role_id',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Relacionamento com a tabela de Cargos (Roles)
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Relacionamento: Um usuário PODE TER um perfil de Embarcador
     */
    public function embarcador()
    {
        return $this->hasOne(Embarcador::class);
    }

    /**
     * Relacionamento: Um usuário PODE TER um perfil de Motorista
     */
    public function motorista()
    {
        return $this->hasOne(Motorista::class);
    }
}