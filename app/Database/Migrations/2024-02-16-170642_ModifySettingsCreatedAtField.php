<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class ModifySettingsCreatedAtField extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('settings', [
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);
    }

    public function down()
    {
        //
    }
}
