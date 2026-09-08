<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('email_templates')->update([
            'html_body' => DB::raw(
                "REPLACE(html_body,
                    'https://monflow.fr/assets/img/spotiflix%20(1).png',
                    'https://client.monflow.fr/icons/icon-192.png'
                )"
            ),
        ]);

        // Also fix width/height attributes on the new logo for existing rows
        DB::table('email_templates')->update([
            'html_body' => DB::raw(
                "REPLACE(html_body,
                    '<img src=\"https://client.monflow.fr/icons/icon-192.png\" alt=\"MonFlow\" width=\"160\"',
                    '<img src=\"https://client.monflow.fr/icons/icon-192.png\" alt=\"MonFlow\" width=\"64\" height=\"64\" style=\"display:block;margin:0 auto;border-radius:12px\"'
                )"
            ),
        ]);
    }

    public function down(): void
    {
        DB::table('email_templates')->update([
            'html_body' => DB::raw(
                "REPLACE(html_body,
                    'https://client.monflow.fr/icons/icon-192.png',
                    'https://monflow.fr/assets/img/spotiflix%20(1).png'
                )"
            ),
        ]);
    }
};
