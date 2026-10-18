<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Firmen-Kürzel ergänzen
        Schema::table('companies', function (Blueprint $table) {
            $table->string('short_code', 10)->default('EBS')->after('name');
        });

        // 2. Kunden erweitern & Firmenbindung lösen
        Schema::table('clients', function (Blueprint $table) {
            $table->string('short_code', 10)->nullable()->after('name');
            $table->string('color', 20)->default('#2563EB')->after('short_code');
            $table->string('email_dispo')->nullable()->after('color');
            $table->string('email_wagenliste')->nullable()->after('email_dispo');
            $table->string('email_schadwagen')->nullable()->after('email_wagenliste');
            $table->string('phone_dispo')->nullable()->after('email_schadwagen');
            $table->string('phone_backup')->nullable()->after('phone_dispo');
        });

        // 3. Rollen-Tabelle anlegen
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('label');
            $table->json('permissions')->nullable();
            $table->timestamps();
        });

        // Benutzer-Tabelle an Rollen anbinden
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('company_id')->constrained('roles')->nullOnDelete();
        });

        // 4. Termine: Leistungsarten und optionale Zugnummer/Notiz
        Schema::table('appointments', function (Blueprint $table) {
            $table->json('services')->nullable()->after('title'); // ['Az', 'Rd', 'WU', 'Sonstiges']
            $table->string('service_other')->nullable()->after('services');
            $table->string('train_number')->nullable()->after('service_other');
        });

        // 5. Abwesenheiten (Urlaub / Krank)
        Schema::create('absences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // urlaub, krank, schulung
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absences');
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['services', 'service_other', 'train_number']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
        });
        Schema::dropIfExists('roles');
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['short_code', 'color', 'email_dispo', 'email_wagenliste', 'email_schadwagen', 'phone_dispo', 'phone_backup']);
        });
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('short_code');
        });
    }
};
