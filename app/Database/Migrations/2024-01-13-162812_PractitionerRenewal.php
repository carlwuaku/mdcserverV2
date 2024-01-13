<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class PractitionerRenewal extends Migration
{
    public function up()
    {
        $this->forge->addField('id');
        $this->forge->addField([

            'uuid' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => false,
                'unique' => true,
                'default' => new RawSql('UUID()'),
            ],
            'registration_number' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => false
            ],
            'credits' => [
                'type' => 'VARCHAR',
                'constraint' => '10',
                'null' => true,
                'default' => null
            ],
            'deleted' => [
                'type' => 'TINYINT',
                'constraint' => '4',
                'null' => true,
                'default' => null
            ],
            'deleted_by' => [
                'type' => 'BIGINT',
                'constraint' => '20',
                'null' => true,
                'default' => null
            ],
            'modified_by' => ['type' => 'BIGINT', 'constraint' => '20', 'null' => true,
                'default' => null],
            'created_by' => ['type' => 'BIGINT', 'constraint' => '20', 'null' => true,
                'default' => null],
            
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

            'year' => [
                'type' => 'DATE',
                'null' => true,
                'default' => null,
            ],

            'receipt' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
                'default'=> null
            ],
            'qr_code' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
                'default'=> null
            ],
            'qr_text' => [
                'type' => 'VARCHAR',
                'constraint' => '500',
                'null' => true,
                'default'=> null
            ],
            'expiry' => [
                'type' => 'DATE',
                'null' => true,
                'default'=> null
            ],
            'specialty' => [
                'type' => 'VARCHAR',
                'constraint' => '500',
                'null' => true,
                'default'=> null
            ],
            'place_of_work' => [
                'type' => 'VARCHAR',
                'constraint' => '1000',
                'null' => true,
                'default'=> null
            ],
            'region' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
                'default'=> null
            ],
            'institution_type' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
                'default'=> null
            ],
            'district' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
                'default'=> null
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['Approved','Pending Payment','Pending Approval'],
                'null' => true,
                'default'=> null
            ],
            'payment_date' => [
                'type' => 'DATE',
                'null' => true,
                'default'=> null
            ],
            'payment_file' => [
                'type' => 'VARCHAR',
                'constraint' => '1500',
                'null' => true,
                'default'=> null
            ],
            'payment_file_date' => [
                'type' => 'DATE',
                'null' => true,
                'default'=> null
            ],
            'subspecialty' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default'=> null
            ],
            'college_membership' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default'=> null
            ],
            'payment_invoice_number' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default'=> null
            ],
            'first_name'=> [
                'type' => 'VARCHAR',
               'constraint' => '255',
               'null' => true,
               'default'=> null,
            ],
            'middle_name'=> [
                'type' => 'VARCHAR',
               'constraint' => '255',
               'null' => true,
               'default' => null
            ],
            'last_name'=> [
                'type' => 'VARCHAR',
               'constraint' => '255',
               'null' => true,
               'default'=> null,
            ],
            'title'=> [
                'type' => 'VARCHAR',
               'constraint' => '10',
               'null' => true,
               'default' => null
            ],
            'maiden_name'=> [
                'type' => 'VARCHAR',
               'constraint' => '255',
               'null' => true,
               'default' => null
            ],
            'marital_status'=> [
                'type' => 'VARCHAR',
               'constraint' => '255',
               'null' => true,
               'default' => null
            ],
            'picture'=> [
                'type' => 'VARCHAR',
               'constraint' => '5000',
               'null' => true,
               'default' => null
            ],
        ]);
        $this->forge->addKey('registration_number', false);
        $this->forge->addKey('created_on', false);
        $this->forge->addKey('receipt', false);
        $this->forge->addKey('qr_code', false);
        $this->forge->addKey('qr_text', false);
        $this->forge->addKey('expiry', false);
        $this->forge->addKey('specialty', false);
        $this->forge->addKey('place_of_work', false);
        $this->forge->addKey('institution_type', false);
        $this->forge->addKey('region', false);
        $this->forge->addKey('district', false);
        $this->forge->addKey('status', false);
        $this->forge->addKey('payment_date', false);
        $this->forge->addKey('subspecialty', false);
        $this->forge->addKey('college_membership', false);
        $this->forge->addKey('payment_invoice_number', false);
        $this->forge->addForeignKey('registration_number', 'practitioners', 'registration_number', 'CASCADE', 'CASCADE');

        $this->forge->createTable('practitioner_renewal', true);

    }

    public function down()
    {
        $this->forge->dropTable('practitioner_renewal');
    }
}
