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
        Schema::create('reports', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('title');
            $table->text('description');
            $table->string('status')->default('pending');
            $table->string('reporter_id')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('reporter_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
