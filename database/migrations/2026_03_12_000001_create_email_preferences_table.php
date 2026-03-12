<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = config('email-preferences.table_names.preferences', 'email_preferences');

        Schema::create($table, function (Blueprint $table) {
            $table->id();

            // Polymorphic so the package works with any notifiable model, not just User
            $table->morphs('notifiable');

            // Category key matching a key in config('email-preferences.categories')
            $table->string('category');

            // instant | daily | weekly | never
            $table->string('frequency', 20)->default('instant');

            // Null = subscribed, set = unsubscribed
            $table->timestamp('unsubscribed_at')->nullable();

            $table->timestamps();

            // One row per notifiable per category
            // Note: morphs() already creates the (notifiable_type, notifiable_id) index
            $table->unique(['notifiable_type', 'notifiable_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            config('email-preferences.table_names.preferences', 'email_preferences')
        );
    }
};
