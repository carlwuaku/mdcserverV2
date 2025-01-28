<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCpdAttendanceUUID extends Migration
{
    public function up()
    {
        $this->forge->addColumn('cpd_attendance', [
            'uuid' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => true,
                'unique' => true
            ]
        ]);
        $trigger = "
       CREATE TRIGGER before_insert_cpd_attendance
       BEFORE INSERT ON cpd_attendance
       FOR EACH ROW
       BEGIN
        SET NEW.uuid = UUID();
       END;";
        $this->db->query($trigger);
    }

    public function down()
    {
        //
    }
}
