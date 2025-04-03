<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRenewalPrintTemplatesFK extends Migration
{
    public function up()
    {//DOES NOT WORK
        $this->forge->modifyColumn('print_templates', [
            'template_name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,  // Keep the original constraint
                'collation' => 'utf8_general_ci',  // Set the desired collation
            ],
        ]);

    }

    public function down()
    {

    }
}
