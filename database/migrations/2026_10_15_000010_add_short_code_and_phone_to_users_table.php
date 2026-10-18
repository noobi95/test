<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'short_code')) {
                $table->string('short_code', 10)->nullable()->after('name');
            }
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 50)->nullable()->after('email');
            }
        });

        // Automatische Initial-Kürzel vergeben, falls leer
        foreach (User::all() as $u) {
            if (empty($u->short_code)) {
                $parts = explode(' ', trim($u->name));
                $code = '';
                if (count($parts) >= 2) {
                    $code = strtoupper(substr($parts[0], 0, 1) . substr($parts[count($parts) - 1], 0, 1));
                } else {
                    $code = strtoupper(substr($u->name, 0, 3));
                }
                $u->update(['short_code' => $code]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['short_code', 'phone']);
        });
    }
};
