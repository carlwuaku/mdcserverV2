<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDocumentsUniqueIdIndexes extends Migration
{
    public function up()
    {
        $this->forge->addKey('unique_id', false, false, 'document_unique_id_index');
        $this->forge->addKey('table_name', false, false, 'document_table_name_index');
        $this->forge->addKey('table_row_uuid', false, false, 'document_table_row_uuid_index');
        $this->forge->processIndexes('documents');
    }

    public function down()
    {
        //
    }
}
