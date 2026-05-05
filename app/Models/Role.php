<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    // Permite que o Seeder e o Controller criem/busquem roles
    protected $fillable = ['slug', 'name'];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}