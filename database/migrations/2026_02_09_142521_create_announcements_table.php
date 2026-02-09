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
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained('center_profiles')->onDelete('cascade');
            $table->enum('type', ['recruiting', 'promotional']); // B2B o B2C
            $table->string('title');
            $table->text('description');
            $table->text('content')->nullable();
            
            // Campi specifici per annunci recruiting
            $table->string('contract_type')->nullable(); // es. "Part-time", "Full-time", "Collaborazione"
            $table->integer('weekly_hours')->nullable();
            $table->json('requirements')->nullable(); // Requisiti minimi professionali
            
            // Campi per entrambi i tipi
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'is_active']);
            $table->index(['center_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
