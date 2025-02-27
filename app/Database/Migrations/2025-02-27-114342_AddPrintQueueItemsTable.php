<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPrintQueueItemsTable extends Migration
{
    public function up()
    {
        $query = "
        CREATE TABLE print_queue_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    queue_id INT NOT NULL,
    item_data JSON NOT NULL,
    status ENUM('pending', 'printed', 'failed') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    print_order INT NOT NULL,
    error_message TEXT,
    FOREIGN KEY (queue_id) REFERENCES print_queues(queue_id) ON DELETE CASCADE,
    INDEX idx_queue_id_status (queue_id, status)
);
        ";
        $this->db->query($query);
    }

    public function down()
    {
        //
    }
}
