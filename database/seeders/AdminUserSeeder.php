<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get Super Admin role ID
        $superAdminRole = DB::table('tbl_roles')
            ->where('role_name', 'Super Admin')
            ->first();

        if (!$superAdminRole) {
            $this->command->error('Super Admin role not found. Please run RolesSeeder first.');
            return;
        }

        $now = Carbon::now();
        
        $adminUser = [
            'username' => 'admin',
            'email_id' => 'admin@pdts.local',
            'password' => Hash::make('Join@1234'),
            'first_name' => 'Admin',
            'last_name' => 'User',
            'mobile_no' => null,
            'user_type' => $superAdminRole->id,
            'status' => 1,
            'remember_token' => null,
            'last_logged_on' => null,
            'profile_image' => null,
            'address' => null,
            'reference_id' => null,
            'serial_number' => null,
            'qr_code' => null,
            'otp_code' => null,
            'otp_expiry' => null,
            'created_by' => 1,
            'created_on' => $now,
            'updated_by' => 1,
            'updated_on' => $now,
            'is_delete' => 0,
        ];

        // Check if admin user already exists
        $exists = DB::table('tbl_user')
            ->where('email_id', $adminUser['email_id'])
            ->exists();

        if (!$exists) {
            DB::table('tbl_user')->insert($adminUser);
            $this->command->info("Admin user created successfully.");
            $this->command->info("Email: {$adminUser['email_id']}");
            $this->command->info("Password: Join@1234");
        } else {
            // Update existing admin user
            DB::table('tbl_user')
                ->where('email_id', $adminUser['email_id'])
                ->update([
                    'password' => Hash::make('Join@1234'),
                    'user_type' => $superAdminRole->id,
                    'status' => 1,
                    'updated_on' => $now,
                ]);
            $this->command->info("Admin user already exists. Password and role updated.");
            $this->command->info("Email: {$adminUser['email_id']}");
            $this->command->info("Password: Join@1234");
        }
    }
}

