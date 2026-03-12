<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = config('email-preferences.table_names.logs', 'email_preference_logs');

        Schema::create($table, function (Blueprint $table) {
            $table->id();

            $table->morphs('notifiable');

            $table->string('category');

            // subscribed | unsubscribed | frequency_changed
            $table->string('action', 30);

            // preference_center | unsubscribe_link | api
            $table->string('via', 30);

            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            // Immutable — no updated_at
            $table->timestamp('created_at')->useCurrent()->index();

            // morphs() already creates (notifiable_type, notifiable_id) index
            $table->index(['notifiable_type', 'notifiable_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            config('email-preferences.table_names.logs', 'email_preference_logs')
        );
    }
};
