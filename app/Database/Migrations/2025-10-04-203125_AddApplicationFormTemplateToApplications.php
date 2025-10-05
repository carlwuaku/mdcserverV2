<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddApplicationFormTemplateToApplications extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists("template", 'application_forms')) {
            $this->forge->addColumn('application_forms', [
                "template" => [
                    'type' => 'JSON',
                    'null' => true,
                ]
            ]);
        }
    }

    public function down()
    {
        //
    }
}
