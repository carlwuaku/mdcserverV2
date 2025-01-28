<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddApplicationStages extends Migration
{
    public function up()
    {
        $this->forge->addColumn('application_form_templates', [
            "stages" => [
                'type' => 'JSON',
                'null' => false
            ],
            'initialStage' => [
                'type' => 'TEXT',
                'null' => false
            ],
            'finalStage' => [
                'type' => 'TEXT',
                'null' => false
            ]
        ]);
    }

    public function down()
    {
        //
    }
}
