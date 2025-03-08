<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPrintQueueItemsTable extends Migration
{
    public function up()
    {
        $query = "
        CREATE TABLE print_queue_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL,
    queue_uuid CHAR(36) NOT NULL,
    item_data JSON NOT NULL,
    status ENUM('pending', 'printed', 'failed') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    print_order INT NOT NULL,
    error_message TEXT,
    FOREIGN KEY (queue_uuid) REFERENCES print_queues(uuid) ON DELETE CASCADE,
    INDEX idx_queue_id_status (id, status),
    CONSTRAINT print_queue_item_uuid UNIQUE (uuid)
);
        ";
        $this->db->query($query);
    }

    public function down()
    {
        //
    }
}
