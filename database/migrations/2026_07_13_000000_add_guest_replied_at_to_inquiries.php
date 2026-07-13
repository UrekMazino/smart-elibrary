<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inquiries') && ! Schema::hasColumn('inquiries', 'guest_replied_at')) {
            Schema::table('inquiries', function (Blueprint $table) {
                $table->timestamp('guest_replied_at')->nullable()->after('message');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('inquiries', 'guest_replied_at')) {
            Schema::table('inquiries', function (Blueprint $table) {
                $table->dropColumn('guest_replied_at');
            });
        }
    }
};
