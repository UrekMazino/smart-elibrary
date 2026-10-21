<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * users.email is the login identifier but was unique only by form validation, so two sign-ups at
 * the same moment could create two accounts with one address. Declare it UNIQUE. The column's
 * collation is case-insensitive, so Juan@x.ph and juan@x.ph count as the same address — the same
 * rule the `unique:users` validation applies (soft-deleted accounts included).
 *
 * Changes nothing if duplicates already exist: merge or rename those accounts, then migrate again.
 */
return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('users')->select('email')->groupBy('email')->havingRaw('count(*) > 1')->get()->count();
        if ($duplicates) {
            throw new RuntimeException("Cannot make users.email unique: {$duplicates} address(es) belong to more than one account. "
                . 'Find them with: SELECT email, COUNT(*) FROM users GROUP BY email HAVING COUNT(*) > 1;');
        }

        Schema::table('users', fn (Blueprint $table) => $table->unique('email'));
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropUnique(['email']));
    }
};
