<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ModifyAdditionalQualificationEndDate extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('practitioner_additional_qualifications', [
            'end_date' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'default' => null,
            ],
        ]);
    }

    public function down()
    {
        //
    }
}
