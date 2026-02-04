<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserRole;
use Illuminate\Console\Command;

class FixUserRoles extends Command
{
    protected $signature = 'fix:user-roles';
    protected $description = 'Fix user roles - ensure admin users have admin role in user_roles table';

    public function handle()
    {
        $this->info('Checking user roles...');

        $adminEmails = ['admin@wizflussi.ba', 'admin@wizflussi.app'];
        $accountantEmails = ['racunovodstvo@wizflussi.ba', 'racunovodstvo@wizflussi.app'];
        $viewerEmails = ['pregled@wizflussi.ba', 'pregled@wizflussi.app'];

        // Fix admin users
        foreach ($adminEmails as $email) {
            $user = User::where('email', $email)->first();
            if ($user) {
                $role = UserRole::where('user_id', $user->id)->first();
                if (!$role) {
                    UserRole::create(['user_id' => $user->id, 'role' => 'admin']);
                    $this->info("✓ Created admin role for: {$email}");
                } elseif ($role->role !== 'admin') {
                    $role->update(['role' => 'admin']);
                    $this->info("✓ Updated role to admin for: {$email}");
                } else {
                    $this->info("✓ Admin role already correct for: {$email}");
                }
            }
        }

        // Fix accountant users
        foreach ($accountantEmails as $email) {
            $user = User::where('email', $email)->first();
            if ($user) {
                $role = UserRole::where('user_id', $user->id)->first();
                if (!$role) {
                    UserRole::create(['user_id' => $user->id, 'role' => 'accountant']);
                    $this->info("✓ Created accountant role for: {$email}");
                } elseif ($role->role !== 'accountant') {
                    $role->update(['role' => 'accountant']);
                    $this->info("✓ Updated role to accountant for: {$email}");
                } else {
                    $this->info("✓ Accountant role already correct for: {$email}");
                }
            }
        }

        // Fix viewer users
        foreach ($viewerEmails as $email) {
            $user = User::where('email', $email)->first();
            if ($user) {
                $role = UserRole::where('user_id', $user->id)->first();
                if (!$role) {
                    UserRole::create(['user_id' => $user->id, 'role' => 'viewer']);
                    $this->info("✓ Created viewer role for: {$email}");
                } elseif ($role->role !== 'viewer') {
                    $role->update(['role' => 'viewer']);
                    $this->info("✓ Updated role to viewer for: {$email}");
                } else {
                    $this->info("✓ Viewer role already correct for: {$email}");
                }
            }
        }

        $this->info('');
        $this->info('Done! All user roles have been checked and fixed.');
        
        return 0;
    }
}
