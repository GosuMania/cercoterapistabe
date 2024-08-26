<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateParentPatientProfilesTable extends Migration
{
    public function up()
    {
        Schema::create('parent_patient_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('relationship')->nullable();
            $table->string('patient_name')->nullable();
            $table->date('patient_birthdate')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('parent_patient_profiles');
    }
}

