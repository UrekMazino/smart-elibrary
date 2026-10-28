<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Guests on the statistics watch (RANKING_INTEGRITY_PLAN.md step 2.6; SECURITY_NOTES.md section 6):
 *  - views.visitor_hash: a guest's keyed hash of IP address and browser (visitorHash()), so the watch can tell
 *    guests apart; never the address itself;
 *  - visitor_addresses: the IP address behind each hash, shown on the watch to developer and super admin only.
 * Both are deleted 90 days on by the nightly visitors:forget (App\Console\Commands\ForgetVisitors).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('views', function (Blueprint $table) {
            $table->char('visitor_hash', 32)->nullable()->after('user_id')->index();
        });

        Schema::create('visitor_addresses', function (Blueprint $table) {
            $table->id();
            $table->char('visitor_hash', 32)->unique();
            $table->string('ip_address', 45);
            $table->timestamp('first_seen_at')->nullable()->index();
            $table->timestamp('last_seen_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitor_addresses');
        Schema::table('views', function (Blueprint $table) {
            $table->dropIndex(['visitor_hash']);
            $table->dropColumn('visitor_hash');
        });
    }
};
