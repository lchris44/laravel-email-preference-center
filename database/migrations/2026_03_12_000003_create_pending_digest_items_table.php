<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pending_digest_items', function (Blueprint $table) {
            $table->id();
            $table->string('notifiable_type');
            $table->unsignedBigInteger('notifiable_id');
            $table->string('category')->default('digest');
            $table->string('frequency', 20);
            $table->string('type');
            $table->json('payload');
            $table->timestamps();

            $table->index(['notifiable_type', 'notifiable_id', 'frequency', 'category'], 'pending_digest_notifiable_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_digest_items');
    }
};
