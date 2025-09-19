<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\User;
use App\Models\Project;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // Create demo account
        $account = Account::firstOrCreate([
            'slug' => 'demo-account'
        ], [
            'name' => 'Demo Pet Rescue',
            'plan' => 'pro',
            'settings' => [
                'timezone' => 'America/New_York',
                'default_language' => 'en',
            ],
        ]);

        // Create admin user
        $admin = User::firstOrCreate([
            'email' => 'admin@rehome.app'
        ], [
            'account_id' => $account->id,
            'name' => 'Admin User',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        // Create team member
        $teamMember = User::firstOrCreate([
            'email' => 'team@rehome.app'
        ], [
            'account_id' => $account->id,
            'name' => 'Team Member',
            'password' => Hash::make('password'),
            'role' => 'team',
        ]);

        // Create client user
        $client = User::firstOrCreate([
            'email' => 'client@rehome.app'
        ], [
            'account_id' => $account->id,
            'name' => 'Client User',
            'password' => Hash::make('password'),
            'role' => 'client',
        ]);

        // Create demo projects
        $projects = [
            [
                'name' => 'Downtown Adoption Center',
                'description' => 'Main adoption facility located in the heart of downtown. Specializes in cats and small dogs.',
                'status' => 'active',
                'user_id' => $admin->id,
            ],
            [
                'name' => 'Rural Dog Rescue',
                'description' => 'Large property rescue focusing on larger breed dogs and rehabilitation.',
                'status' => 'active',
                'user_id' => $teamMember->id,
            ],
            [
                'name' => 'Emergency Pet Shelter',
                'description' => 'Temporary housing for pets displaced by emergencies and natural disasters.',
                'status' => 'inactive',
                'user_id' => $client->id,
            ],
        ];

        foreach ($projects as $projectData) {
            Project::firstOrCreate([
                'name' => $projectData['name'],
                'account_id' => $account->id,
            ], [
                'account_id' => $account->id,
                'user_id' => $projectData['user_id'],
                'description' => $projectData['description'],
                'status' => $projectData['status'],
                'metadata' => [
                    'created_by_seeder' => true,
                    'demo_data' => true,
                ],
            ]);
        }

        $this->command->info('Demo data created successfully!');
        $this->command->info('Login credentials:');
        $this->command->info('  Admin: admin@rehome.app / password');
        $this->command->info('  Team:  team@rehome.app / password');
        $this->command->info('  Client: client@rehome.app / password');
    }
}
