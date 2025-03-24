<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SetDistrictsRegionidNull extends Migration
{
    public function up()
    {
        $fields = [
            'region_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'default' => null
            ],
        ];
        $this->forge->modifyColumn('districts', $fields);
    }

    public function down()
    {
        //
    }
}
