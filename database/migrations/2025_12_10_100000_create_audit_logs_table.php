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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Entity information
            $table->string('entity_type', 50)->index(); // Report, Message
            $table->uuid('entity_id')->index();
            
            // Action performed
            $table->enum('action', ['Create', 'Read', 'Update', 'Delete'])->index();
            
            // Audit data
            $table->json('payload')->nullable(); // Full entity data snapshot
            $table->string('actor_ip', 45)->nullable(); // IPv4/IPv6 anonymized
            
            // Blockchain-like chain
            $table->string('previous_hash', 64)->index(); // SHA256 of previous record
            $table->string('hash', 64)->unique(); // SHA256 of this record
            
            $table->timestamp('created_at')->useCurrent();
            
            // Indexes for verification and queries
            $table->index(['entity_type', 'entity_id', 'created_at']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
