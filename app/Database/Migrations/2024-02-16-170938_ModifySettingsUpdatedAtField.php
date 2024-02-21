<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class ModifySettingsUpdatedAtField extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('settings', [
            'updated_at' => [
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
