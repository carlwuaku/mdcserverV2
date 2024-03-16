<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ChangePractitionerStatus extends Migration
{
    public function up()
    {
        //alter the status column of practitioner table. Change it from int to varchar ('Alive', 'Deceased').         
        $this->forge->modifyColumn('practitioners', [
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'default' => 'Alive',
                'null' => true
            ]
        ]);
        $this->db->query("update practitioners set status = 'Deceased' where status = 0");
        $this->db->query("update practitioners set status = 'Alive' where status = 1");
    }

    public function down()
    {
        //

    }
}
