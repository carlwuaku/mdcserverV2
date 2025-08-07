<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTimestampsToOnlinePayments extends Migration
{
    public function up()
    {
        $this->forge->addColumn('online_payments', [
            'deleted_at' => [
                'type' => 'datetime',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'datetime',
                'null' => true,
            ],
        ]);
        if ($this->db->fieldExists('created_on', 'online_payments')) {
            $this->forge->modifyColumn('online_payments', [
                'created_on' => [
                    'name' => 'created_at',
                    'type' => 'datetime',
                    'null' => true
                ]
            ]);
        }
    }

    public function down()
    {
        //
    }
}
