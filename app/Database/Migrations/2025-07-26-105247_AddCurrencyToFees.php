<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCurrencyToFees extends Migration
{
    public function up()
    {


        //add currency to fees table
        $this->forge->addColumn('fees', [
            'currency' => [
                'type' => 'ENUM',
                'constraint' => ['GHS', '$', 'GBP', ''],
                'null' => true,
                'default' => 'GHS'
            ]
        ]);
    }

    public function down()
    {
        //
    }
}
