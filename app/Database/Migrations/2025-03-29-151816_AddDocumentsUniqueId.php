<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDocumentsUniqueId extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('unique_id', 'documents')) {
            $this->forge->addColumn('documents', [
                'unique_id' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'default' => null,
                ],
            ]);
        }
        if (!$this->db->fieldExists('table_name', 'documents')) {
            $this->forge->addColumn('documents', [
                'table_name' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'default' => null,
                ],
            ]);
        }
        if (!$this->db->fieldExists('table_row_uuid', 'documents')) {
            $this->forge->addColumn('documents', [
                'table_row_uuid' => [
                    'type' => 'CHAR',
                    'constraint' => 36,
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
