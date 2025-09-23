<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class AddDefaultPrintTemplates extends Migration
{
    public function up()
    {
        //add a new field to the print_templates table for default templates
        $this->forge->addColumn('print_templates', [
            'is_default' => [
                'type' => 'BOOLEAN',
                'null' => true,
                'default' => new RawSql('FALSE'),
            ],
        ]);
    }


    public function down()
    {
        //
    }
}
