<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $adminRole   = Role::firstOrCreate(['name' => 'admin',  'guard_name' => 'web']);
        $creatorRole = Role::firstOrCreate(['name' => 'creator', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        $admin = User::firstOrCreate(['email' => 'admin@tilawa.app'], ['name' => 'Administrator', 'password' => Hash::make('password')]);
        $admin->syncRoles([$adminRole]);
        $creator = User::firstOrCreate(['email' => 'creator@tilawa.app'], ['name' => 'Creator', 'password' => Hash::make('password')]);
        $creator->syncRoles([$creatorRole]);
    }
}
