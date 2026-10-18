<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Company;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            [
                'label' => 'Administrator',
                'permissions' => ['manage_companies', 'manage_users', 'manage_clients', 'manage_roles', 'edit_appointments', 'manage_absences'],
            ]
        );

        $dispoRole = Role::firstOrCreate(
            ['name' => 'disponent'],
            [
                'label' => 'Disponent',
                'permissions' => ['manage_clients', 'edit_appointments', 'manage_absences'],
            ]
        );

        $employeeRole = Role::firstOrCreate(
            ['name' => 'employee'],
            [
                'label' => 'Tf / Rangierer (Nur Ansicht)',
                'permissions' => [],
            ]
        );

        // Firmenkürzel setzen
        Company::where('name', 'like', '%EBS%')->update(['short_code' => 'EBS']);
        Company::where('name', 'like', '%Partner%')->update(['short_code' => 'P-AG']);

        // Kundenkürzel & Farben setzen
        Client::where('name', 'like', '%DB Cargo%')->update([
            'short_code' => 'DBC',
            'color' => '#DC2626',
            'phone_dispo' => '0800 123456',
            'phone_backup' => '0800 654321',
            'email_dispo' => 'dispo@dbcargo.com',
            'email_wagenliste' => 'wagenliste@dbcargo.com',
            'email_schadwagen' => 'schadwagen@dbcargo.com',
        ]);

        Client::where('name', 'like', '%SBB%')->update([
            'short_code' => 'SBB',
            'color' => '#059669',
            'phone_dispo' => '0987 654321',
            'email_dispo' => 'dispo@sbb.ch',
        ]);

        // Rollen zuweisen
        User::where('role', 'admin')->update(['role_id' => $adminRole->id]);
        User::where('role', 'employee')->update(['role_id' => $dispoRole->id]);
    }
}
