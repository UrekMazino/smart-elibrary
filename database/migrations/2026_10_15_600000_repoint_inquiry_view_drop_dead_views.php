<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * view_inquiries took each inquiry's title from the legacy `holdings` table, which is empty, so the
 * title was always NULL. It now reads the title from the record's OPAC row (view_catalogs).
 *
 * view_to_reviews and view_searches also read `holdings` (so they returned nothing useful) and
 * nothing uses them: the To Review list is built from downloads + reads, and search is
 * Meilisearch. Both are dropped, with their models.
 */
return new class extends Migration
{
    private const INQUIRIES = "select `a`.`id` AS `id`,`a`.`user_id` AS `user_id`,`a`.`fullname` AS `fullname`,`a`.`contactnum` AS `contactnum`,`a`.`email` AS `email`,`a`.`catalog_id` AS `catalog_id`,`a`.`subject` AS `subject`,`a`.`message` AS `message`,`a`.`created_at` AS `created_at`,`a`.`updated_at` AS `updated_at`,`b`.`title_statement` AS `title_statement`,date_format(`a`.`created_at`,'%M %d, %Y %T') AS `created_at_string`,(select count(`reply_inquiries`.`id`) from `reply_inquiries` where (`reply_inquiries`.`inquiry_id` = `a`.`id`)) AS `total_msg`,(select count(`view_inquiry_threads`.`id`) from `view_inquiry_threads` where ((`view_inquiry_threads`.`id` = `a`.`id`) and (`view_inquiry_threads`.`seen` is null) and (`view_inquiry_threads`.`replied_by` <> `a`.`user_id`))) AS `new_reply` from (`inquiries` `a` left join {source} `b` on((`b`.`catalog_id` = `a`.`catalog_id`)))";

    public function up(): void
    {
        DB::statement('CREATE OR REPLACE VIEW `view_inquiries` AS ' . str_replace('{source}', '`view_catalogs`', self::INQUIRIES));
        DB::statement('DROP VIEW IF EXISTS `view_to_reviews`, `view_searches`');
    }

    public function down(): void
    {
        DB::statement('CREATE OR REPLACE VIEW `view_inquiries` AS ' . str_replace('{source}', '`holdings`', self::INQUIRIES));
        DB::statement("CREATE OR REPLACE VIEW `view_to_reviews` AS select `a`.`id` AS `id`,`a`.`catalog_id` AS `catalog_id`,`a`.`user_id` AS `user_id`,`a`.`to_review` AS `to_review`,`a`.`created_at` AS `created_at`,`a`.`updated_at` AS `updated_at`,`b`.`title_statement` AS `title_statement` from (`downloads` `a` left join `holdings` `b` on((`b`.`catalog_id` = `a`.`catalog_id`)))");
        DB::statement("CREATE OR REPLACE VIEW `view_searches` AS select `a`.`id` AS `id`,concat(`a`.`title_statement`,`a`.`summary`) AS `search`,`a`.`material_type_id` AS `material_type_id`,`b`.`name` AS `material` from (`holdings` `a` left join `materials` `b` on((`b`.`id` = `a`.`material_type_id`)))");
    }
};
