<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password;

    public function definition(): array
    {
        $roleId = DB::table('roles')->where('slug', 'motorista')->value('id');
        
        if (empty($roleId)) {
            $roleId = DB::table('roles')->insertGetId([
                'slug'       => 'motorista',
                'name'       => 'Motorista',
                'description'=> 'Perfil Teste',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return [
            'name'              => fake()->name(),
            'email'             => fake()->unique()->safeEmail(),
            'phone'             => fake()->unique()->numerify('55119########'),
            'email_verified_at' => now(),
            'password'          => static::$password ??= Hash::make('password'),
            'remember_token'    => Str::random(10),
            'role_id'           => $roleId,
            'status'            => 'active',
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}