<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddHmacKeyToApiKeys extends Migration
{
    public function up()
    {
        $this->forge->addColumn('api_keys', [
            'hmac_secret' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'after' => 'key_secret_hash',
                'comment' => 'Encrypted HMAC secret for signature verification (stored encrypted, NOT hashed)',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('api_keys', 'hmac_secret');
    }
}
