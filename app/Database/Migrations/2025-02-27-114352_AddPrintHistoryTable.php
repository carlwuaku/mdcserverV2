<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPrintHistoryTable extends Migration
{
    public function up()
    {
        $query = "
        CREATE TABLE print_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL,
    queue_uuid CHAR(36) NOT NULL,
    item_uuid CHAR(36),
    printed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    printed_by INT,
    print_status ENUM('success', 'failed') DEFAULT 'success',
    error_details TEXT,
    CONSTRAINT print_queue_history_uuid UNIQUE (uuid),
    FOREIGN KEY (queue_uuid) REFERENCES print_queues(uuid)  ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (item_uuid) REFERENCES print_queue_items(uuid)   ON DELETE CASCADE ON UPDATE CASCADE
);
        ";
        $this->db->query($query);
    }

    public function down()
    {
        //
    }
}
