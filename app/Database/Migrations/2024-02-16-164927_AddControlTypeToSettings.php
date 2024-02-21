<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddControlTypeToSettings extends Migration
{
    public function up()
    {
        $table = 'settings';
        if (!$this->db->fieldExists('control_type', $table)) {
            $this->forge->addColumn($table, [
               'control_type' => [
                   'type' => 'VARCHAR',
                   'constraint'=> 255,
                   'null' => true,
                   'default'=> null,
               ],
            ]);
        }
    }

    public function down()
    {
        //
    }
}
