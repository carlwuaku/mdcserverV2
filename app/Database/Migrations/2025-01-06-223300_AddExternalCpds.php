<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;
class AddExternalCpds extends Migration
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
            'provider' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'attendance_date' => [
                'type' => 'DATE',
                'null' => false
            ],
            'topic' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => NULL
            ],
            'created_on' => [
                'type' => 'TIMESTAMP',
                'null' => true,
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
            'credits' => [
                'type' => 'INT',
                'null' => true,
                'default' => 0
            ],
            'certificate_link' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => NULL
            ],
            'external_cpd_id' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => NULL
            ],
            'raw_data' => [
                'type' => 'LONGTEXT',
                'null' => true,
                'default' => NULL
            ],

        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('external_cpd_attendance');
    }

    public function down()
    {
        //
    }
}
