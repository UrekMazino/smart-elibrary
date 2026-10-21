<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ban / Block flags on user accounts.
        //   is_banned  -> cannot log in AND cannot use the Read feature
        //   is_blocked -> cannot use chat / inquiry (can still log in)
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'is_banned')) {
                $table->boolean('is_banned')->default(0)->after('usertype');
            }
            if (! Schema::hasColumn('users', 'is_blocked')) {
                $table->boolean('is_blocked')->default(0)->after('is_banned');
            }
        });

        // Per-reservation admin<->patron chat thread (mirrors the inquiry reply thread,
        // but scoped to a single reservation via reservation_code).
        if (! Schema::hasTable('reservation_messages')) {
            Schema::create('reservation_messages', function (Blueprint $table) {
                $table->id();
                $table->string('reservation_code')->index();
                $table->unsignedBigInteger('user_id')->nullable();   // the patron this thread belongs to
                $table->unsignedBigInteger('sender_id')->nullable(); // who wrote this message
                $table->string('sender_role', 20)->default('admin'); // 'admin' | 'patron'
                $table->text('message');
                $table->timestamp('seen_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'is_blocked')) $table->dropColumn('is_blocked');
            if (Schema::hasColumn('users', 'is_banned'))  $table->dropColumn('is_banned');
        });

        Schema::dropIfExists('reservation_messages');
    }
};
