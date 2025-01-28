<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRegisterTypeToLicenses extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('register_type', 'licenses')) {
            $this->forge->addColumn('licenses', [
                'register_type' => [
                    'type' => 'VARCHAR',
                    'null' => true,
                    'default' => 'Provisional',
                    'constraint' => 255,
                ],
            ]);
        }
    }

    public function down()
    {
        //
    }
}
