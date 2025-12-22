<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $knownUsers = [
            [
                'first_name' => 'admin',
                'last_name' => 'admin',
                'phone' => '1',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'avatar' => null,
                'id_card' => null,
                'birth_date' => '1990-01-15',
                'status' => 'approved',
            ],
            [
                'first_name' => 'Mohammed',
                'last_name' => 'Ali',
                'phone' => '+963992222222',
                'password' => Hash::make('owner123'),
                'role' => 'owner',
                'avatar' => null,
                'id_card' => null,
                'birth_date' => '1985-05-20',
                'status' => 'approved',
            ],
            [
                'first_name' => 'Ahmad',
                'last_name' => 'Hassan',
                'phone' => '+963993333333',
                'password' => Hash::make('tenant123'),
                'role' => 'tenant',
                'avatar' => null,
                'id_card' => null,
                'birth_date' => '1995-08-10',
                'status' => 'approved',
            ],
            [
                'first_name' => 'Fatima',
                'last_name' => 'Omar',
                'phone' => '+963994444444',
                'password' => Hash::make('password123'),
                'role' => 'owner',
                'avatar' => null,
                'id_card' => null,
                'birth_date' => '1988-12-03',
                'status' => 'pending',
            ],
            [
                'first_name' => 'Sara',
                'last_name' => 'Youssef',
                'phone' => '+963995555555',
                'password' => Hash::make('password123'),
                'role' => 'tenant',
                'avatar' => null,
                'id_card' => null,
                'birth_date' => '1998-03-25',
                'status' => 'rejected',
            ],
        ];

        foreach ($knownUsers as $userData) {
            User::create($userData);
        }

        // 2. Create random users using factory
        // Create 5 admin users (approved)
        User::factory()->count(5)->admin()->approved()->create();

        // Create 15 owner users (mix of statuses)
        User::factory()->count(10)->owner()->approved()->create();
        User::factory()->count(3)->owner()->pending()->create();
        User::factory()->count(2)->owner()->state(['status' => 'rejected'])->create();

        // Create 30 tenant users (mix of statuses)
        User::factory()->count(20)->tenant()->approved()->create();
        User::factory()->count(7)->tenant()->pending()->create();
        User::factory()->count(3)->tenant()->state(['status' => 'rejected'])->create();

        // Total: 5 admins + 15 owners + 30 tenants + 5 known users = 55 users

        // Output info
        $this->command->info('✅ Created 55 users:');
        $this->command->info('   - 6 admins (1 specific + 5 random)');
        $this->command->info('   - 17 owners (2 specific + 15 random)');
        $this->command->info('   - 32 tenants (3 specific + 29 random)');
        $this->command->info('   - Status: approved, pending, rejected');
    }
}
