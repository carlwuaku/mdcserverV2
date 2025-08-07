<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePaymentFilesTable extends Migration
{
    public function up()
    {

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 20,
                'AUTO_INCREMENT' => true
            ],
            'uuid' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => false,
            ],
            'payment_uuid' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => false,
            ],
            'file_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'file_path' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'file_size' => [
                'type' => 'INT',
                'null' => true,
            ],
            'file_type' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'file_category' => [
                'type' => 'ENUM',
                'constraint' => ['receipt', 'proof_of_payment', 'bank_statement', 'other'],
                'default' => 'receipt',
                'null' => false,
            ],
            'uploaded_by' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'uploaded_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('uuid');
        $this->forge->addKey('payment_uuid');
        $this->forge->addForeignKey('payment_uuid', 'payments', 'uuid', 'CASCADE', 'CASCADE');
        $this->forge->createTable('payment_files');

        // Add UUID trigger for file_id
        $this->db->query("ALTER TABLE payment_files MODIFY uuid CHAR(36) DEFAULT (UUID())");
    }

    public function down()
    {
        $this->forge->dropTable('payment_files');
    }
}
