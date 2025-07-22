<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ChangeUserRegionIdToRegion extends Migration
{
    public function up()
    {
        if ($this->db->fieldExists('regionId', 'users')) {
            $this->forge->dropColumn('users', 'regionId');

        }
        $this->forge->addColumn('users', [
            'region' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'default' => null
            ]
        ]);

        $this->forge->addForeignKey('region', 'regions', 'name', 'CASCADE', 'RESTRICT');
        $this->forge->processIndexes('users');

    }

    public function down()
    {
        //
    }
}
