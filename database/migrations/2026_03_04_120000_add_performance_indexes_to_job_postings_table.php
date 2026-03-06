<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            $table->index(['created_at'], 'job_postings_created_at_idx');
            $table->index(['title', 'location'], 'job_postings_title_location_idx');
        });
    }

    public function down(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            $table->dropIndex('job_postings_created_at_idx');
            $table->dropIndex('job_postings_title_location_idx');
        });
    }
};
