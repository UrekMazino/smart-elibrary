<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The OPAC chat button (General inquiries). Anyone can send one, signed in or not:
 *  - kind: 'material' (asked from a record page) or 'general' (the chat button).
 *  - guest_name / guest_contactnum / guest_email: a guest has no account, so the inquiry holds the
 *    only copy of their details; staff answer them by email. A signed-in sender's details are still
 *    read from their account (R15, 2026_10_16_600000), never copied here.
 *  - guest_replied_at (already there): when staff marked a guest's inquiry as answered by email.
 * view_inquiries reads the sender from the account or, for a guest, from these columns.
 */
return new class extends Migration
{
    /** view_inquiries, with {sender} and {extra} standing for the parts that differ. */
    private const VIEW = "select `a`.`id` AS `id`,`a`.`user_id` AS `user_id`,{sender},`a`.`catalog_id` AS `catalog_id`,`a`.`subject` AS `subject`,`a`.`message` AS `message`,`a`.`created_at` AS `created_at`,`a`.`updated_at` AS `updated_at`,`b`.`title_statement` AS `title_statement`,date_format(`a`.`created_at`,'%M %d, %Y %T') AS `created_at_string`,(select count(`reply_inquiries`.`id`) from `reply_inquiries` where (`reply_inquiries`.`inquiry_id` = `a`.`id`)) AS `total_msg`,(select count(`view_inquiry_threads`.`id`) from `view_inquiry_threads` where ((`view_inquiry_threads`.`id` = `a`.`id`) and (`view_inquiry_threads`.`seen` is null) and (`view_inquiry_threads`.`replied_by` <> `a`.`user_id`))) AS `new_reply`{extra} from ((`inquiries` `a` left join `view_catalogs` `b` on((`b`.`catalog_id` = `a`.`catalog_id`))) left join `users` `u` on((`u`.`id` = `a`.`user_id`)))";

    public function up(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->string('kind', 20)->default('material')->after('id')->index();
            $table->string('guest_name', 150)->nullable()->after('user_id');
            $table->string('guest_contactnum', 30)->nullable()->after('guest_name');
            $table->string('guest_email', 190)->nullable()->after('guest_contactnum');
        });

        DB::statement('CREATE OR REPLACE VIEW `view_inquiries` AS ' . strtr(self::VIEW, [
            '{sender}' => 'coalesce(`u`.`name`,`a`.`guest_name`) AS `fullname`,coalesce(`u`.`contactnum`,`a`.`guest_contactnum`) AS `contactnum`,coalesce(`u`.`email`,`a`.`guest_email`) AS `email`',
            '{extra}'  => ',`a`.`kind` AS `kind`,`a`.`guest_replied_at` AS `guest_replied_at`',
        ]));
    }

    /** A guest inquiry holds the only copy of its sender's details, so going back stops while any exist. */
    public function down(): void
    {
        $guests = DB::table('inquiries')->whereNull('user_id')->count();
        if ($guests) {
            throw new RuntimeException("Not changed: $guests inquiry(ies) were sent by guests and hold the only copy of the sender's "
                . 'name, phone and email. Answer or export them, delete them, then roll back again.');
        }

        DB::statement('CREATE OR REPLACE VIEW `view_inquiries` AS ' . strtr(self::VIEW, [
            '{sender}' => '`u`.`name` AS `fullname`,`u`.`contactnum` AS `contactnum`,`u`.`email` AS `email`',
            '{extra}'  => '',
        ]));
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropIndex(['kind']);
            $table->dropColumn(['kind', 'guest_name', 'guest_contactnum', 'guest_email']);
        });
    }
};
