<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AdRenewalPrintTemplateFK extends Migration
{
    public function up()
    {
        $this->forge->addForeignKey('print_template', 'print_templates', 'template_name', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('online_print_template', 'print_templates', 'template_name', 'CASCADE', 'RESTRICT');
        $this->forge->processIndexes('license_renewal');
    }

    public function down()
    {
        //
    }
}
