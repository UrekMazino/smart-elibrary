<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sessions kept in the database (SESSION_DRIVER=database), so a user can see where they are signed in and sign other
 * sessions out (OWASP ASVS 7.5.2; App\Services\BrowserSessions). Laravel's own table. For every visitor, signed in
 * or not, it holds the IP address and browser (user agent) until the session ends or SESSION_LIFETIME after its
 * last request (docs/implemented/SECURITY_NOTES.md section 5).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};
