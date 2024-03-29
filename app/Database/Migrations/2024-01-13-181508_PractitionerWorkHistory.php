<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class PractitionerWorkHistory extends Migration
{
    public function up()
    {
        $this->forge->addField('id');
        $this->forge->addField([

            'uuid' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => false,
                'unique' => true,
            ],
            'registration_number' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => false
            ],
            'institution' => [
                'type' => 'VARCHAR',
                'constraint' => '500',
                'null' => false
            ],
            'start_date' => [
                'type' => 'DATE',
                'null' => true,
                'default' => null
            ],
            'end_date' => [
                'type' => 'DATE',
                'null' => true,
                'default' => null
            ],
            'location' => [
                'type' => 'VARCHAR',
                'constraint' => '500',
                'null' => false,
            ],
            'region' => [
                'type' => 'VARCHAR',
                'constraint' => '500',
                'null' => true,
                'default' => null
            ],
            'position' => [
                'type' => 'VARCHAR',
                'constraint' => '500',
                'null' => false,
            ],
            'institution_type' => [
                'type' => 'VARCHAR',
                'constraint' => '500',
                'null' => false,
            ],
            'created_by' => ['type' => 'BIGINT', 'constraint' => '20', 'null' => true,
                'default' => null],

            'created_on' => [
                'type' => 'TIMESTAMP',
                'null' => true,
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
            'modified_on' => [
                'type' => 'TIMESTAMP',
                'null' => true,
                'default' => new RawSql('CURRENT_TIMESTAMP'),
                'on_update' => new RawSql('CURRENT_TIMESTAMP'),
            ],
            'modified_by' => [
                'type' => 'BIGINT',
                'constraint' => '20',
                'null' => true,
                'default' => null
            ],
            'deleted_by' => [
                'type' => 'BIGINT',
                'constraint' => '20',
                'null' => true,
                'default' => null
            ],

            'status' => [
                'type' => 'ENUM',
                'constraint' => ['Approved', 'Pending Approval'],
                'null' => true,
                'default' => null
            ],
            'deleted' => [
                'type' => 'TINYINT',
                'constraint' => '4',
                'null' => true,
                'default' => null
            ],
        ]);
        $this->forge->addKey('registration_number', false);
        $this->forge->addKey('institution', false);
        $this->forge->addKey('region', false);
        $this->forge->addForeignKey('registration_number', 'practitioners', 'registration_number', 'CASCADE', 'CASCADE', 'work_history_reg_num');

        $this->forge->createTable('practitioner_work_history', true, [
            'ENGINE' => 'InnoDB',
        ]);

    }

    public function down()
    {
        //
    }
}
