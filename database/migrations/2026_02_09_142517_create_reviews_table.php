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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reviewer_id')->constrained('users')->onDelete('cascade'); // Genitore che recensisce
            $table->morphs('reviewable'); // reviewed_id e reviewed_type (therapist/center)
            $table->tinyInteger('rating')->unsigned(); // 1-5 stelle
            $table->text('comment')->nullable();
            $table->text('response')->nullable(); // Risposta del terapista/centro
            $table->timestamp('response_at')->nullable();
            $table->timestamp('reported_at')->nullable(); // Per moderazione
            $table->enum('status', ['approved', 'pending', 'reported'])->default('approved');
            $table->timestamps();

            // Un genitore può recensire un terapista/centro una sola volta
            $table->unique(['reviewer_id', 'reviewable_id', 'reviewable_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
