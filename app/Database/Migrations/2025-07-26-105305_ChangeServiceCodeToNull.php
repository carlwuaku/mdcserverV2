<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ChangeServiceCodeToNull extends Migration
{
    public function up()
    {
        //rename the fees->service_code column to not null
        $this->forge->modifyColumn('fees', [
            'service_code' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false
            ]
        ]);
    }

    public function down()
    {
        //
    }
}
