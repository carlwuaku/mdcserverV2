<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SetSubspecialtiesSpecialtyidNull extends Migration
{
    public function up()
    {
        $fields = [
            'specialty_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'default' => null
            ],
        ];
        $this->forge->modifyColumn('subspecialties', $fields);
    }

    public function down()
    {
        //
    }
}
