<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The status of a reservation or loan as its patron last saw it on their Reservations page. A status
 * that differs (approved, declined, checked out, returned by staff) is an update the patron hasn't
 * seen: the account menu's Reservations badge counts it until they open the page. Comparing
 * statuses, rather than marking each staff action, catches a change made from any page.
 *
 * Existing rows start as seen, so upgrading doesn't flag every old reservation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('circulations', function (Blueprint $table) {
            $table->unsignedTinyInteger('patron_seen_status')->nullable()->after('status');
        });
        DB::table('circulations')->update(['patron_seen_status' => DB::raw('status')]);
    }

    public function down(): void
    {
        Schema::table('circulations', function (Blueprint $table) {
            $table->dropColumn('patron_seen_status');
        });
    }
};
