<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Record the user agent alongside each view.
 *
 * saveView() now refuses to log crawler hits, but no bot list is ever complete. Storing the agent
 * means a future mis-classification can be audited and cleaned up — without it, bot and human rows
 * are indistinguishable after the fact, which is exactly why crawler pollution of this table would
 * otherwise be permanent. See DEPLOYMENT_CHECKLIST.md §7 / OPAC_SEO_PLAN.md §3.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('views', function (Blueprint $table) {
            $table->string('user_agent', 512)->nullable()->after('ip_view');
        });
    }

    public function down(): void
    {
        Schema::table('views', function (Blueprint $table) {
            $table->dropColumn('user_agent');
        });
    }
};
