<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUniqueIdToPrintQueue extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('item_unique_id', 'print_queue_items')) {
            $this->forge->addColumn('print_queue_items', [
                'item_unique_id' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'default' => null,
                    'comment' => 'Unique ID for the item. could be a license number, intern code, etc',
                ],
            ]);
        }
    }

    public function down()
    {
        //
    }
}
