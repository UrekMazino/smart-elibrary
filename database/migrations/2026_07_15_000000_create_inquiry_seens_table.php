<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Per-admin "seen" receipts for inquiries (Messenger-style read receipts).
        // A row means admin `user_id` viewed inquiry `inquiry_id` up to `seen_at`.
        // Multiple admins -> multiple rows per inquiry. Drives both the seen avatars
        // in the chat and the per-admin "awaiting reply" notification.
        if (! Schema::hasTable('inquiry_seens')) {
            Schema::create('inquiry_seens', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('inquiry_id');
                $table->unsignedBigInteger('user_id'); // the admin who viewed
                $table->timestamp('seen_at')->nullable();
                $table->timestamps();
                $table->unique(['inquiry_id', 'user_id']);
                $table->index('inquiry_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiry_seens');
    }
};
