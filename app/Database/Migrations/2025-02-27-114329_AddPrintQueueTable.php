<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPrintQueueTable extends Migration
{
    public function up()
    {
        $query = "
        CREATE TABLE print_queues (
    queue_id INT AUTO_INCREMENT PRIMARY KEY,
    queue_name VARCHAR(100) NOT NULL,
    template_id INT NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    priority TINYINT DEFAULT 5,
    FOREIGN KEY (template_id) REFERENCES print_templates(template_id),
    INDEX idx_queue_status (status)
);
        ";
        $this->db->query($query);
    }

    public function down()
    {
        //
    }
}
