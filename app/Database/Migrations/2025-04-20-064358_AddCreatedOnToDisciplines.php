<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCreatedOnToDisciplines extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('created_at', 'housemanship_disciplines')) {
            $this->forge->addColumn('housemanship_disciplines', [
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'default' => null,
                ],
            ]);
        }
        if (!$this->db->fieldExists('modified_at', 'housemanship_disciplines')) {
            $this->forge->addColumn('housemanship_disciplines', [
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'default' => null,
                ],
            ]);
        }
        if (!$this->db->fieldExists('deleted_at', 'housemanship_disciplines')) {
            $this->forge->addColumn('housemanship_disciplines', [
                'deleted_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
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
