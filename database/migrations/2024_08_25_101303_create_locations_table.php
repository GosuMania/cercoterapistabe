<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();

            // Relazione con l'utente
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Utility field per l'utente (es. "Casa", "Studio")
            $table->string('label')->nullable();

            // Campi Indirizzo
            $table->string('street_address', 255)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->char('country_code', 2)->nullable();

            // Coordinate
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->geography('geom', 'point', 4326)->nullable();

            $table->text('formatted_address')->nullable();
            $table->boolean('is_default')->default(false); // Per impostare un indirizzo principale
            $table->timestamps();

            $table->spatialIndex('geom');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
