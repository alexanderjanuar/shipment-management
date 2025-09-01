<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Add start_date column
            $table->date('start_date')
                  ->nullable()
                  ->after('description')
                  ->comment('Tanggal mulai proyek');

            // Drop foreign key constraints first
            $table->dropForeign(['client_id']);
            $table->dropForeign(['pic_id']);
            
            // Drop columns
            $table->dropColumn([
                'client_id',
                'pic_id', 
                'priority',
                'type'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Add back the dropped columns
            $table->foreignId('client_id')
                  ->after('id')
                  ->constrained()
                  ->comment('Client yang memiliki proyek');
                  
            $table->foreignId('pic_id')
                  ->nullable()
                  ->after('sop_id')
                  ->constrained('users')
                  ->onDelete('set null')
                  ->comment('Person in Charge untuk proyek ini');
                  
            $table->enum('priority', ['urgent','normal','low'])
                  ->default('normal')
                  ->after('description');
                  
            $table->enum('type', ['single','monthly','yearly'])
                  ->after('priority');
            
            // Drop start_date
            $table->dropColumn('start_date');
        });
    }
};