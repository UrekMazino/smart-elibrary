<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * "Continue with Google" for patrons (App\Http\Controllers\Auth\GoogleSignInController):
 * - google_id: the Google account an eLibrary account is linked to, by Google's own id for it (the "sub"), which stays
 *   the same when its address changes. Empty until the owner first continues with Google.
 * - password may be empty: an account made with Google has none until its owner sets one. Password sign-in refuses
 *   an account without one (Laravel's EloquentUserProvider); it confirms with Google instead (App\Services\GoogleSignIn).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id', 64)->nullable()->unique()->after('email');
            $table->string('password', 255)->nullable()->change();   // keeps its length (Schema::defaultStringLength is shorter)
        });
    }

    public function down(): void
    {
        // Accounts made with Google get a password nobody knows: they can't sign in until it is reset.
        DB::table('users')->whereNull('password')->orderBy('id')->each(
            fn ($user) => DB::table('users')->where('id', $user->id)->update(['password' => Hash::make(Str::random(64))])
        );
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['google_id']);
            $table->dropColumn('google_id');
            $table->string('password', 255)->nullable(false)->change();
        });
    }
};
