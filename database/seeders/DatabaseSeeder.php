<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        DB::table('settings')->insert([
            'status' => 0,
            'bot_key' => '5098450629:AAESoApAjpaUzVWJuwy54bTGW9uwh0ejLrw',
            'chat_id' => 1099509877,
            'max_grids' => null,
            'reply_to_message' => 1,
            'created_at' => date('Y-m-d h:i:s')
        ]);
    }
}
