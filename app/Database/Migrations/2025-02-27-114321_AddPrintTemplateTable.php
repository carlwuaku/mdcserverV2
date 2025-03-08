<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPrintTemplateTable extends Migration
{
    public function up()
    {
        $query = "
        CREATE TABLE print_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL,
    template_name VARCHAR(100) NOT NULL,
    template_content TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    active BOOLEAN DEFAULT TRUE,
    CONSTRAINT uc_template_name UNIQUE (template_name),
    CONSTRAINT uc_uuid UNIQUE (uuid)
);
        ";
        $this->db->query($query);
    }

    public function down()
    {
        //
    }
}
