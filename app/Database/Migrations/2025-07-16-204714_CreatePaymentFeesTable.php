<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePaymentFeesTable extends Migration
{
    public function up()
    {
        $this->forge->addField('id');
        $this->forge->addField([

            'payer_type' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => false
            ],
            'rate' => [
                'type' => 'DOUBLE',
                'null' => false
            ],
            'created_on' => [
                'type' => 'TIMESTAMP',
                'null' => true,
                'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),

            ],
            'category' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default' => NULL,
            ],
            'service_code' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
                'default' => 'GHS',
            ],
            'chart_of_account' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'default' => NULL,
            ],
            'supports_variable_amount' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'default' => 'no',
            ]
        ]);

        $this->forge->addKey('payer_type', false);
        $this->forge->addKey('name', false);
        $this->forge->addKey('rate', false);
        $this->forge->addKey('category', false);
        $this->forge->addKey('id', true);

        $this->forge->createTable('fees', true, [
            'ENGINE' => 'InnoDB',
        ]);
    }

    public function down()
    {
        //
    }
}
