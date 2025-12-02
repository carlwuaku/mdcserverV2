<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;
class SetAcationsAuditCreatedAt extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('actions_audit', [

            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'default' => new RawSql('CURRENT_TIMESTAMP')
            ],
        ]);
    }

    public function down()
    {
        //
    }
}
