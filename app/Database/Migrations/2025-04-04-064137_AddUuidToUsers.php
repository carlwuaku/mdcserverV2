<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUuidToUsers extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('uuid', 'users')) {
            $this->forge->addColumn('users', [
                'uuid' => [
                    'type' => 'CHAR',
                    'null' => true,
                    'constraint' => 36,
                    'default' => null,
                ],
            ]);
        }
    }

    public function down()
    {
        //
    }
}
