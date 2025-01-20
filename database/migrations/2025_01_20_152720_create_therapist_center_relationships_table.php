<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTherapistCenterRelationshipsTable extends Migration
{
    public function up()
    {
        Schema::create('therapist_center_relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('therapist_id')->constrained('therapist_profiles')->onDelete('cascade');
            $table->foreignId('center_id')->constrained('center_profiles')->onDelete('cascade');
            $table->enum('status', ['Pending', 'Accepted', 'Declined'])->default('Pending');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('therapist_center_relationships');
    }
}

