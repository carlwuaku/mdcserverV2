<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPrintHistoryTable extends Migration
{
    public function up()
    {
        $query = "
        CREATE TABLE print_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    queue_id INT NOT NULL,
    item_id INT,
    printed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    printed_by INT,
    print_status ENUM('success', 'failed') DEFAULT 'success',
    error_details TEXT,
    FOREIGN KEY (queue_id) REFERENCES print_queues(queue_id),
    FOREIGN KEY (item_id) REFERENCES print_queue_items(item_id)
);
        ";
        $this->db->query($query);
    }

    public function down()
    {
        //
    }
}
