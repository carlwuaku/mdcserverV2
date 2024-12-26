<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CpdProviders extends Migration
{
    public function up()
    {
        $this->forge->addField('id');
        $this->forge->addField([

            'name' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'modified_on' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_on' => [
                'type' => 'TIMESTAMP',
                'null' => true,
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
            'location' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'phone' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => NULL,
            ],
            'email' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => NULL,
            ]

        ]);


        $this->forge->createTable('cpd_providers', true, [
            'ENGINE' => 'InnoDB',
        ]);

    }

    public function down()
    {
        //
    }
}
