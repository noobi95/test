<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        if (!Schema::hasTable('company_document')) {
            Schema::create('company_document', function (Blueprint $table) {
                $table->id();
                $table->foreignId('document_id')->constrained()->cascadeOnDelete();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->timestamps();
            });
        }
        // Bestehende company_id Eintraege in Zwischentabelle uebertragen
        if (Schema::hasColumn('documents', 'company_id')) {
            $docs = DB::table('documents')->whereNotNull('company_id')->get();
            foreach ($docs as $d) {
                DB::table('company_document')->updateOrInsert([
                    'document_id' => $d->id,
                    'company_id' => $d->company_id,
                ]);
            }
        }
    }
    public function down(): void {
        Schema::dropIfExists('company_document');
    }
};
