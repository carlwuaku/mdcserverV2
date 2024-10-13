<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;
class AddLicenseRenewal extends Migration
{
    public function up()
    {
        $this->forge->addField('id');
        $this->forge->addField([

            'uuid' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => true,
                'unique' => true
            ],
            'license_number' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => false
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'default' => null
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'default' => null
            ],
            'modified_by' => [
                'type' => 'BIGINT',
                'constraint' => '20',
                'null' => true,
                'default' => null
            ],
            'created_by' => [
                'type' => 'BIGINT',
                'constraint' => '20',
                'null' => true,
                'default' => null
            ],

            'created_on' => [
                'type' => 'TIMESTAMP',
                'null' => true,
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
            'modified_on' => [
                'type' => 'TIMESTAMP',
                'null' => true,
                'default' => new RawSql('CURRENT_TIMESTAMP'),
                'on_update' => new RawSql('CURRENT_TIMESTAMP'),
            ],

            'start_date' => [
                'type' => 'DATE',
                'null' => true,
                'default' => null,
            ],

            'receipt' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
                'default' => null
            ],
            'qr_code' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => null
            ],
            'qr_text' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => null
            ],
            'expiry' => [
                'type' => 'DATE',
                'null' => true,
                'default' => null
            ],
            'status' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => 'Pending Approval'
            ],
            'batch_number' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => null
            ],
            'payment_date' => [
                'type' => 'DATE',
                'null' => true,
                'default' => null
            ],
            'payment_file' => [
                'type' => 'VARCHAR',
                'constraint' => '1500',
                'null' => true,
                'default' => null
            ],
            'payment_file_date' => [
                'type' => 'DATE',
                'null' => true,
                'default' => null
            ],
            'payment_invoice_number' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default' => null
            ],
            'approve_online_certificate' => [
                'type' => 'ENUM',
                'constraint' => ['Yes', 'No'],
                'null' => true,
                'default' => 'no'
            ],
            'online_certificate_start_date' => [
                'type' => 'DATE',
                'null' => true,
                'default' => null
            ],
            'online_certificate_end_date' => [
                'type' => 'DATE',
                'null' => true,
                'default' => null
            ],

            'picture' => [
                'type' => 'VARCHAR',
                'constraint' => '5000',
                'null' => true,
                'default' => null
            ],
        ]);
        $this->forge->addKey('created_on', false);
        $this->forge->addKey('receipt', false);
        $this->forge->addKey('qr_code', false);
        $this->forge->addKey('qr_text', false);
        $this->forge->addKey('expiry', false);

        $this->forge->addKey('status', false);
        $this->forge->addKey('payment_date', false);
        $this->forge->addKey('payment_invoice_number', false);
        $this->forge->addKey('batch_number', false);

        $this->forge->createTable('license_renewal', true, [
            'ENGINE' => 'InnoDB',
        ]);
    }

    public function down()
    {
        //
    }
}
