<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTherapistProfilesTable extends Migration
{
    public function up()
    {
        Schema::create('therapist_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('profession');
            $table->json('therapies')->nullable(); // modificato da specializations
            // $table->json('availability');  rimosso availability
            $table->text('bio')->nullable();
            $table->decimal('hourly_rate', 10, 2);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('therapist_profiles');
    }
}


