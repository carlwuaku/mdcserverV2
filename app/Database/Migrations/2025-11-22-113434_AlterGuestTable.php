<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class AlterGuestTable extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('guests', [
            'email_verified_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'default' => null
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'default' => new RawSql('CURRENT_TIMESTAMP')
            ],
        ]);
    }

    public function down()
    {

    }
}
