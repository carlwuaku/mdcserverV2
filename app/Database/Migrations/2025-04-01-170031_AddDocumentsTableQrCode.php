<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDocumentsTableQrCode extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('qr_code', 'documents')) {
            $this->forge->addColumn('documents', [
                'qr_code' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'default' => null,
                ],
            ]);
        }
    }

    public function down()
    {
        //
    }
}
