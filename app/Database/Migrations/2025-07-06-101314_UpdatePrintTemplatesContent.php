<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdatePrintTemplatesContent extends Migration
{
    public function up()
    {
        if ($this->db->fieldExists('template_content', 'print_templates')) {
            // Modify the template_content field to be longtext
            $this->forge->modifyColumn('print_templates', [
                'template_content' => [
                    'type' => 'LONGTEXT',
                    'null' => false
                ]
            ]);
        }
    }

    public function down()
    {
        //
    }
}
