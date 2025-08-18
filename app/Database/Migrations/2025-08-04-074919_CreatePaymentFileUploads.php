<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreatePaymentFileUploads extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 20,
                'AUTO_INCREMENT' => true
            ],
            'file_path' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'invoice_uuid' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => false,
            ],
            'payment_date' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
                'default' => 'Pending',
            ],
            'reference_number' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'default' => null,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);
        $this->forge->addKey('id', true);

        $this->forge->addKey('invoice_uuid', false, true);
        $this->forge->addKey('status');
        $this->forge->addKey('payment_date');
        $this->forge->addKey('created_at');
        $this->forge->addKey('reference_number');
        $this->forge->addForeignKey('invoice_uuid', 'invoices', 'uuid', 'CASCADE', 'CASCADE');
        $this->forge->createTable('payment_file_uploads', true);

    }

    public function down()
    {
        //
    }
}
