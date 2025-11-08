<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddApplicationTemplateCriteria extends Migration
{
    public function up()
    {

        if (!$this->db->fieldExists("criteria", 'application_form_templates')) {
            $this->forge->addColumn('application_form_templates', [
                "criteria" => [
                    'type' => 'JSON',
                    'null' => true,
                    'default' => null
                ]
            ]);
        }
    }

    public function down()
    {
        //
    }
}
