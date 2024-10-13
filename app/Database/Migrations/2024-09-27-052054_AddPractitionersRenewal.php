<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;
class AddPractitionersRenewal extends Migration
{
    public function up()
    {
        $this->forge->addField('id');
        $this->forge->addField([

            'renewal_id' => [
                'type' => 'INT',
                'null' => true,
            ],
            'license_number' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => false
            ],

            'specialty' => [
                'type' => 'VARCHAR',
                'constraint' => '500',
                'null' => true,
                'default' => null
            ],
            'place_of_work' => [
                'type' => 'VARCHAR',
                'constraint' => '1000',
                'null' => true,
                'default' => null
            ],
            'region' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
                'default' => null
            ],
            'institution_type' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
                'default' => null
            ],
            'district' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
                'default' => null
            ],

            'subspecialty' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default' => null
            ],
            'college_membership' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default' => null
            ],

            'first_name' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default' => null,
            ],
            'middle_name' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default' => null
            ],
            'last_name' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default' => null,
            ],
            'title' => [
                'type' => 'VARCHAR',
                'constraint' => '10',
                'null' => true,
                'default' => null
            ],
            'maiden_name' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default' => null
            ],
            'marital_status' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default' => null
            ]
        ]);
        $this->forge->addKey('license_number', false);

        $this->forge->addKey('specialty', false);
        $this->forge->addKey('place_of_work', false);
        $this->forge->addKey('institution_type', false);
        $this->forge->addKey('region', false);
        $this->forge->addKey('district', false);

        $this->forge->addKey('subspecialty', false);
        $this->forge->addKey('college_membership', false);

        $this->forge->createTable('practitioners_renewal', true, [
            'ENGINE' => 'InnoDB',
        ]);
    }

    public function down()
    {
        //
    }
}
