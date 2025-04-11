<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddApplicationTemplateImageAndRestrictions extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('picture', 'application_form_templates')) {
            $this->forge->addColumn('application_form_templates', [
                'picture' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'default' => null,
                ],
            ]);
        }
        if (!$this->db->fieldExists('restrictions', 'application_form_templates')) {
            $this->forge->addColumn('application_form_templates', [
                'restrictions' => [
                    'type' => 'JSON',
                    'null' => true,
                    'default' => null,
                ],
            ]);
        }
        if (!$this->db->fieldExists('available_externally', 'application_form_templates')) {
            $this->forge->addColumn('application_form_templates', [
                'available_externally' => [
                    'type' => 'BOOLEAN',
                    'null' => true,
                    'default' => 1,
                ],
            ]);
        }
    }

    public function down()
    {
        //
    }
}
