<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DropCpdProviderId extends Migration
{
    public function up()
    {
        $this->forge->dropColumn('cpd_topics', 'provider_id');
    }

    public function down()
    {
        //
    }
}
