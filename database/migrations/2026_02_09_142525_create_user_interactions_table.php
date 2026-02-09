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
        Schema::create('user_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('viewer_id')->constrained('users')->onDelete('cascade'); // Chi ha interagito
            $table->foreignId('viewed_id')->constrained('users')->onDelete('cascade'); // Con chi ha interagito
            $table->enum('interaction_type', ['profile_view', 'info_request', 'search_result']); // Tipo di interazione
            $table->timestamps();

            // Indice per query veloci
            $table->index(['viewed_id', 'interaction_type']);
            $table->index(['viewer_id', 'viewed_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_interactions');
    }
};
