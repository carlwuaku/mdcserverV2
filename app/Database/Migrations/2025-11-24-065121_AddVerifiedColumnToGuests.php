<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddVerifiedColumnToGuests extends Migration
{
    public function up()
    {
        $this->forge->addColumn('guests', [
            'verified' => [
                'type' => 'BOOLEAN',
                'default' => false,
                'null' => false,
                'after' => 'email_verified'
            ]
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('guests', 'verified');
    }
}
