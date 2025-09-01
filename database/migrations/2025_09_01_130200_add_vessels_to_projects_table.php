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
            // Add tug boat reference
            $table->foreignId('tug_boat_id')
                  ->nullable()
                  ->after('pic_id')
                  ->constrained('tug_boats')
                  ->onDelete('set null')
                  ->comment('Kapal tunda yang ditugaskan');
            
            // Add barge reference  
            $table->foreignId('barge_id')
                  ->nullable()
                  ->after('tug_boat_id')
                  ->constrained('barges')
                  ->onDelete('set null')
                  ->comment('Tongkang yang ditugaskan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Drop foreign key constraints
            $table->dropForeign(['tug_boat_id']);
            $table->dropForeign(['barge_id']);
            
            // Drop columns
            $table->dropColumn([
                'tug_boat_id',
                'barge_id'
            ]);
        });
    }
};