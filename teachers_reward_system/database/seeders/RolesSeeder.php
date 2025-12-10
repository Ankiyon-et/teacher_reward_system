<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['role_name' => 'superadmin'],
            ['role_name' => 'schooladmin'],
            ['role_name' => 'teacher'],
        ];

        foreach ($roles as $role) {
            DB::table('user_roles')->updateOrInsert(
                ['role_name' => $role['role_name']],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
