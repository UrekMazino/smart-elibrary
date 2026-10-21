<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * R15, second half. Each inquiry copied the sender's name, phone and email from their account,
 * so a correction or an erasure request had to find every copy. Inquiries are now for signed-in
 * users only (enforced on the route), and view_inquiries reads those three details from the
 * sender's account; the copies are dropped.
 *
 * A guest inquiry (no account) holds the only copy of its sender's details, so the migration stops
 * and counts them rather than erase them; answer or export those first.
 */
return new class extends Migration
{
    /** view_inquiries, with {sender} standing for where name / phone / email come from. */
    private const VIEW = "select `a`.`id` AS `id`,`a`.`user_id` AS `user_id`,{sender},`a`.`catalog_id` AS `catalog_id`,`a`.`subject` AS `subject`,`a`.`message` AS `message`,`a`.`created_at` AS `created_at`,`a`.`updated_at` AS `updated_at`,`b`.`title_statement` AS `title_statement`,date_format(`a`.`created_at`,'%M %d, %Y %T') AS `created_at_string`,(select count(`reply_inquiries`.`id`) from `reply_inquiries` where (`reply_inquiries`.`inquiry_id` = `a`.`id`)) AS `total_msg`,(select count(`view_inquiry_threads`.`id`) from `view_inquiry_threads` where ((`view_inquiry_threads`.`id` = `a`.`id`) and (`view_inquiry_threads`.`seen` is null) and (`view_inquiry_threads`.`replied_by` <> `a`.`user_id`))) AS `new_reply` from (`inquiries` `a` left join `view_catalogs` `b` on((`b`.`catalog_id` = `a`.`catalog_id`))){join}";

    public function up(): void
    {
        $guests = DB::table('inquiries')->whereNull('user_id')->count();
        if ($guests) {
            throw new RuntimeException("Not changed: $guests inquiry(ies) were sent by guests and hold the only copy of the sender's "
                . 'name, phone and email. Answer or export them, delete them, then migrate again.');
        }

        DB::statement('CREATE OR REPLACE VIEW `view_inquiries` AS ' . strtr(self::VIEW, [
            '{sender}' => '`u`.`name` AS `fullname`,`u`.`contactnum` AS `contactnum`,`u`.`email` AS `email`',
            '{join}'   => ' left join `users` `u` on((`u`.`id` = `a`.`user_id`))',
        ]));
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropColumn(['fullname', 'contactnum', 'email']);
        });
    }

    /** Copies come back from the accounts as they are now. */
    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->string('fullname', 255)->nullable()->after('user_id');
            $table->string('contactnum', 255)->nullable()->after('fullname');
            $table->string('email', 255)->nullable()->after('contactnum');
        });
        DB::statement('UPDATE `inquiries` i JOIN `users` u ON u.`id` = i.`user_id` SET i.`fullname` = u.`name`, i.`contactnum` = u.`contactnum`, i.`email` = u.`email`');
        DB::statement('CREATE OR REPLACE VIEW `view_inquiries` AS ' . strtr(self::VIEW, [
            '{sender}' => '`a`.`fullname` AS `fullname`,`a`.`contactnum` AS `contactnum`,`a`.`email` AS `email`',
            '{join}'   => '',
        ]));
    }
};
