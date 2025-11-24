<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUniqueIdToGuests extends Migration
{
    public function up()
    {
        $this->forge->addColumn('guests', [
            'unique_id' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
                'unique' => true,
                'after' => 'uuid'
            ]
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('guests', 'unique_id');
    }
}
