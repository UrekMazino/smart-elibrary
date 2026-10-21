<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * R15, first half. Every page view stored the visitor's IP address and full browser string,
 * anonymous visitors included, indefinitely and in clear, and nothing ever read them (crawlers
 * are filtered out when the request arrives, before anything is stored). The Data Privacy Act
 * (RA 10173, s. 11) asks for data adequate and not excessive for its purpose, so the app stops
 * collecting them and the columns, with every value they held, are dropped. View counts and
 * statistics are unaffected.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('views', function (Blueprint $table) {
            $table->dropColumn(['ip_view', 'user_agent']);
        });
    }

    /** The columns come back empty: the values are gone for good. */
    public function down(): void
    {
        Schema::table('views', function (Blueprint $table) {
            $table->string('ip_view', 255)->nullable()->after('catalog_id');
            $table->string('user_agent', 512)->nullable()->after('ip_view');
        });
    }
};
