<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPrintQueueTable extends Migration
{
    public function up()
    {
        $query = "
        CREATE TABLE print_queues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL,
    queue_name VARCHAR(100) NOT NULL,
    template_uuid CHAR(36) NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    priority TINYINT DEFAULT 5,
    CONSTRAINT print_queue_uuid UNIQUE (uuid),
    FOREIGN KEY (template_uuid) REFERENCES print_templates(uuid) ON DELETE RESTRICT ON UPDATE CASCADE,
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
