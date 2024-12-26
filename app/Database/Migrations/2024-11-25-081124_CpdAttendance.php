<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CpdAttendance extends Migration
{
    public function up()
    /**
     * CREATE TABLE `bf_cpd_attendance` (
  `lic_num` varchar(50) NOT NULL,
  `cpd_id` int(11) NOT NULL,
  `id` int(9) NOT NULL,
  `attendance_date` date DEFAULT '0000-00-00',
  `cpd_session_id` int(11) DEFAULT NULL,
  `venue` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
     */
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
