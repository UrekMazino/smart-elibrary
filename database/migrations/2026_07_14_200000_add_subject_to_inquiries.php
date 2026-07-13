<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inquiries') && ! Schema::hasColumn('inquiries', 'subject')) {
            Schema::table('inquiries', function (Blueprint $table) {
                $table->string('subject')->nullable()->after('catalog_id');
            });
        }

        // view_inquiries reads from inquiries; recreate it so `subject` is exposed to the
        // admin/patron inquiry pages (which query the view, not the base table).
        DB::statement("CREATE OR REPLACE VIEW `view_inquiries` AS
            select
              `a`.`id` AS `id`, `a`.`user_id` AS `user_id`, `a`.`fullname` AS `fullname`,
              `a`.`contactnum` AS `contactnum`, `a`.`email` AS `email`, `a`.`catalog_id` AS `catalog_id`,
              `a`.`subject` AS `subject`, `a`.`message` AS `message`,
              `a`.`created_at` AS `created_at`, `a`.`updated_at` AS `updated_at`,
              `b`.`title_statement` AS `title_statement`,
              date_format(`a`.`created_at`,'%M %d, %Y %T') AS `created_at_string`,
              (select count(`reply_inquiries`.`id`) from `reply_inquiries` where (`reply_inquiries`.`inquiry_id` = `a`.`id`)) AS `total_msg`,
              (select count(`view_inquiry_threads`.`id`) from `view_inquiry_threads` where ((`view_inquiry_threads`.`id` = `a`.`id`) and (`view_inquiry_threads`.`seen` is null) and (`view_inquiry_threads`.`replied_by` <> `a`.`user_id`))) AS `new_reply`
            from (`inquiries` `a` left join `holdings` `b` on((`b`.`catalog_id` = `a`.`catalog_id`)))");
    }

    public function down(): void
    {
        if (Schema::hasColumn('inquiries', 'subject')) {
            Schema::table('inquiries', function (Blueprint $table) {
                $table->dropColumn('subject');
            });
        }
    }
};
