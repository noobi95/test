<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Client;
use App\Models\Company;
use App\Models\Appointment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $firmaA = Company::firstOrCreate(['name' => 'EBS Bahnlogistik GmbH']);
        $firmaB = Company::firstOrCreate(['name' => 'Partner Transport AG']);

        $admin = User::updateOrCreate(
            ['email' => 'admin@mh-ebs.de'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('Admin1234!'),
                'role' => 'admin',
                'company_id' => $firmaA->id,
            ]
        );

        $mitarbeiter1 = User::updateOrCreate(
            ['email' => 'mitarbeiter@mh-ebs.de'],
            [
                'name' => 'Max Mustermann (EBS)',
                'password' => Hash::make('Mitarbeiter1234!'),
                'role' => 'employee',
                'company_id' => $firmaA->id,
            ]
        );

        $mitarbeiter2 = User::updateOrCreate(
            ['email' => 'partner@partner.de'],
            [
                'name' => 'Thomas Bahn (Partner AG)',
                'password' => Hash::make('Mitarbeiter1234!'),
                'role' => 'employee',
                'company_id' => $firmaB->id,
            ]
        );

        $kundeA = Client::firstOrCreate(
            ['email' => 'kunde1@example.com'],
            ['name' => 'DB Cargo Netz', 'company_id' => $firmaA->id, 'phone' => '0123456']
        );

        $kundeB = Client::firstOrCreate(
            ['email' => 'kunde2@example.com'],
            ['name' => 'SBB Cargo International', 'company_id' => $firmaB->id, 'phone' => '0987654']
        );

        Appointment::firstOrCreate(
            ['title' => 'Zugübernahme Lok 185'],
            [
                'company_id' => $firmaA->id,
                'user_id' => $mitarbeiter1->id,
                'client_id' => $kundeA->id,
                'location' => 'Duisburg Hbf Gleis 4',
                'description' => 'Bereitstellung und Bremsprobe',
                'start_time' => now()->setHour(14)->setMinute(0),
                'end_time' => now()->setHour(16)->setMinute(0),
                'status' => 'scheduled',
            ]
        );

        Appointment::firstOrCreate(
            ['title' => 'Paralleler Rangierdienst'],
            [
                'company_id' => $firmaA->id,
                'user_id' => $admin->id,
                'client_id' => $kundeA->id,
                'location' => 'Oberhausen West',
                'description' => 'Gleichzeitiger Einsatz auf Rangierbahnhof',
                'start_time' => now()->setHour(14)->setMinute(0),
                'end_time' => now()->setHour(16)->setMinute(30),
                'status' => 'scheduled',
            ]
        );
    }
}
