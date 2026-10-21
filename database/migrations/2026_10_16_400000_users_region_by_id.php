<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * users.region_id held the region's CODE (regions.regCode, e.g. '13' = NCR), not its id, in a
 * VARCHAR(10): the profile forms submitted the code. The two coincide for no region past IV-A
 * (regions.id 13 is SOCCSKSARGEN, code 12), so the column could not be joined or constrained
 * without silently moving people to another region. It now holds regions.id, with a foreign key
 * (set null when a region is deleted). This was possible only once regions became InnoDB.
 *
 * Every stored code is mapped to its region's id; a code that matches no region stops the
 * migration before anything changes. An empty string means "none" and becomes NULL.
 */
return new class extends Migration
{
    public function up(): void
    {
        $unknown = DB::table('users')->whereNotNull('region_id')->where('region_id', '<>', '')
            ->whereNotIn('region_id', DB::table('regions')->whereNotNull('regCode')->select('regCode'))
            ->distinct()->pluck('region_id');
        if ($unknown->isNotEmpty()) {
            throw new RuntimeException('Not changed: these users.region_id values match no regions.regCode: '
                . $unknown->implode(', ') . '. Correct those accounts, then migrate again.');
        }

        DB::table('users')->where('region_id', '')->update(['region_id' => null]);
        DB::statement('UPDATE `users` u JOIN `regions` r ON r.`regCode` = u.`region_id` SET u.`region_id` = r.`id`');
        DB::statement('ALTER TABLE `users` MODIFY `region_id` INT NULL');
        Schema::table('users', fn (Blueprint $t) => $t->foreign('region_id')->references('id')->on('regions')->nullOnDelete());
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->dropForeign(['region_id']);
            $t->dropIndex('users_region_id_foreign');
        });
        DB::statement('ALTER TABLE `users` MODIFY `region_id` VARCHAR(10) NULL');
        DB::statement('UPDATE `users` u JOIN `regions` r ON r.`id` = u.`region_id` SET u.`region_id` = r.`regCode`');
    }
};
