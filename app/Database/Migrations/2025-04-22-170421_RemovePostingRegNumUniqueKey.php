<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemovePostingRegNumUniqueKey extends Migration
{
    public function up()
    {
        try {
            $this->forge->dropKey('housemanship_postings', 'registration_number');
        } catch (\Throwable $th) {
            log_message('error', 'Failed to drop unique key on registration_number: ' . $th->getMessage());
        }

    }

    public function down()
    {
        //
    }
}
