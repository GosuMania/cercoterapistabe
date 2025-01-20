<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAvailabilitiesTable extends Migration
{
    public function up()
    {
        Schema::create('availabilities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Collega l'utente
            $table->enum('day_of_week', ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']);
            $table->boolean('morning')->default(false); // Disponibilità mattina
            $table->boolean('afternoon')->default(false); // Disponibilità pomeriggio
            $table->boolean('evening')->default(false); // Disponibilità sera
            $table->timestamps();

            // Chiavi esterne e unique constraint
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['user_id', 'day_of_week']); // Ogni utente può avere solo un record per giorno
        });
    }

    public function down()
    {
        Schema::dropIfExists('availabilities');
    }
}
