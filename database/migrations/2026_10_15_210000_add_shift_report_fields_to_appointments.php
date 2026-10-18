<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->datetime('actual_start')->nullable()->after('end_time');
            $table->datetime('actual_end')->nullable()->after('actual_start');
            $table->datetime('travel_out_start')->nullable()->after('actual_end');
            $table->datetime('travel_out_end')->nullable()->after('travel_out_start');
            $table->datetime('travel_back_start')->nullable()->after('travel_out_end');
            $table->datetime('travel_back_end')->nullable()->after('travel_back_start');
            $table->integer('pause_minutes')->default(0)->after('travel_back_end');
            $table->text('report_notes')->nullable()->after('pause_minutes');
            $table->string('report_status')->default('pending')->after('report_notes'); // pending, completed, change_requested
            $table->timestamp('reported_at')->nullable()->after('report_status');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn([
                'actual_start', 'actual_end',
                'travel_out_start', 'travel_out_end',
                'travel_back_start', 'travel_back_end',
                'pause_minutes', 'report_notes',
                'report_status', 'reported_at'
            ]);
        });
    }
};
