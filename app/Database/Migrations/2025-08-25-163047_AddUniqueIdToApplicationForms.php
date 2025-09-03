<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUniqueIdToApplicationForms extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('applicant_unique_id', 'application_forms')) {
            $this->forge->addColumn('application_forms', [
                'applicant_unique_id' => [
                    'type' => 'VARCHAR',
                    'constraint' => 100,
                    'null' => true,
                    'default' => null
                ]
            ]);
        }

        $this->forge->addKey('applicant_unique_id');
        $this->forge->processIndexes('application_forms');
    }

    public function down()
    {
        //
    }
}
