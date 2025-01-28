<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CpdAttendance extends Migration
{
    public function up()
    {
        $this->forge->addField('id');
        $this->forge->addField([

            'license_number' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => false,
            ],
            'cpd_id' => [
                'type' => 'INT',
                'null' => false,
            ],
            'attendance_date' => [
                'type' => 'DATE',
                'null' => false
            ],
            'cpd_session_id' => [
                'type' => 'INT',
                'null' => true,
                'default' => NULL
            ],
            'venue' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default' => NULL
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('cpd_attendance');
    }

    public function down()
    {
        //
    }
}
