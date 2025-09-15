<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {

        $path = database_path('sql/elib_new.sql');

        if (File::exists($path)) {
            $sql = File::get($path);
            DB::unprepared($sql);
        }
    }

    public function down(): void
    {
        // You can either drop the table directly
        // DB::unprepared('DROP TABLE IF EXISTS `model_has_permissions`;');
    }
};
