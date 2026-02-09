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
        // Aggiungi campo onboarding_completed a users
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('onboarding_completed')->default(false)->after('is_premium');
        });

        // Aggiungi campi a center_profiles
        Schema::table('center_profiles', function (Blueprint $table) {
            $table->string('partita_iva')->nullable()->after('center_name');
            $table->string('logo_url')->nullable()->after('description');
        });

        // Aggiungi campi a therapist_profiles
        Schema::table('therapist_profiles', function (Blueprint $table) {
            $table->foreignId('affiliation_center_id')->nullable()->after('hourly_rate')
                ->constrained('center_profiles')->onDelete('set null');
            $table->integer('years_of_experience')->nullable()->after('affiliation_center_id');
        });

        // Aggiungi stati ai messaggi
        Schema::table('messages', function (Blueprint $table) {
            $table->timestamp('sent_at')->nullable()->after('message_content');
            $table->timestamp('delivered_at')->nullable()->after('sent_at');
            $table->timestamp('read_at')->nullable()->after('delivered_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('onboarding_completed');
        });

        Schema::table('center_profiles', function (Blueprint $table) {
            $table->dropColumn(['partita_iva', 'logo_url']);
        });

        Schema::table('therapist_profiles', function (Blueprint $table) {
            $table->dropForeign(['affiliation_center_id']);
            $table->dropColumn(['affiliation_center_id', 'years_of_experience']);
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['sent_at', 'delivered_at', 'read_at']);
        });
    }
};
