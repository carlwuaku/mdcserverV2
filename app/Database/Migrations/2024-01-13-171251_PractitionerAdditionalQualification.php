<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;
class PractitionerAdditionalQualification extends Migration
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
            'qualification' => [
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
            
            'picture'=> [
                'type' => 'VARCHAR',
               'constraint' => '5000',
               'null' => true,
               'default' => null
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['Approved','Pending Approval'],
                'null' => true,
                'default'=> null
            ],
        ]);
        $this->forge->addKey('registration_number', false);
        $this->forge->addKey('institution', false);
        $this->forge->addKey('qualification', false);
        $this->forge->addForeignKey('registration_number', 'practitioners', 'registration_number', 'CASCADE', 'CASCADE','add_qualification_reg_num');

        $this->forge->createTable('practitioner_additional_qualifications', true);

    }

    public function down()
    {
        //
    }
}
