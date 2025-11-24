<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddGuestIDImage extends Migration
{
    public function up()
    {
        $this->forge->addColumn('guests', [
            'id_image' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => NULL,
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('guests', 'id_image');
    }
}
