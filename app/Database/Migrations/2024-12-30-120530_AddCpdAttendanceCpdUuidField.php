<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCpdAttendanceCpdUuidField extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('cpd_uuid', 'cpd_attendance')) {
            $this->forge->addColumn('cpd_attendance', [
                'cpd_uuid' => [
                    'type' => 'VARCHAR',
                    'constraint' => 36,
                    'null' => true,
                ],
            ]);
        }

        // get the provider_uuid from the cpd_providers table using the provider_id
        $this->db->query("update cpd_attendance JOIN cpd_topics ON cpd_attendance.cpd_uuid = cpd_topics.uuid SET cpd_attendance.cpd_uuid = cpd_topics.uuid ");

    }

    public function down()
    {
        //
    }
}
