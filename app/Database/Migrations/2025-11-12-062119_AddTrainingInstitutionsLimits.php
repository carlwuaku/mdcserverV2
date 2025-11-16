<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class AddTrainingInstitutionsLimits extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'auto_increment' => true,
            ],
            'training_institution_uuid' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => false
            ],
            'student_limit' => [
                'type' => 'INT',
                'constraint' => '11',
                'null' => true,
                'default' => 0
            ],
            'year' => [
                'type' => 'YEAR',
                'constraint' => '11',
                'null' => false
            ]
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('training_institution_uuid', false);
        $this->forge->addKey('year', false);
        $this->forge->addForeignKey('training_institution_uuid', 'training_institutions', 'uuid', 'CASCADE', 'CASCADE');
        $this->forge->addUniqueKey(['training_institution_uuid', 'year'], 'training_institution_year');
        $this->forge->createTable(
            'training_institutions_limits',
            true,
            [
                'ENGINE' => 'InnoDB',
            ]
        );
    }

    public function down()
    {
        //
    }
}
