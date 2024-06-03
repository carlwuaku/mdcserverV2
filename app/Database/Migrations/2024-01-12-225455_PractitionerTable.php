<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;
class PractitionerTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 9,
                'auto_increment' => true,
            ],
            'uuid' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => false,
                'unique' => true,
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
               'null' => false,
            ],
            'date_of_birth'=> [
                'type' => 'DATE',
               'null' => false,
            ],
            'registration_number'=> [
                'type' => 'VARCHAR',
               'constraint' => '50',
               'null' => false,
               'unique'=> true
            ],
            'sex'=> [
                'type' => 'VARCHAR',
               'constraint' => '100',
               'null' => false,
            ],
            'registration_date'=> [
                'type' => 'DATE',
               'null' => true,
               'default'=> null
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
            'nationality'=> [
                'type' => 'VARCHAR',
               'constraint' => '100',
               'null' => true,
               'default' => null
            ],
            'qualification_at_registration'=> [
                'type' => 'VARCHAR',
               'constraint' => '100',
               'null' => true,
               'default' => null
            ],
            'training_institution'=> [
                'type' => 'VARCHAR',
               'constraint' => '100',
               'null' => true,
               'default' => null
            ],
            'qualification_date'=> [
                'type' => 'DATE',
               'null' => true,
               'default' => null
            ],
            'status'=> [
                'type' => 'VARCHAR',
               'constraint' => '15',
               'null' => true,
               'default' => null
            ],
            'email'=> [
                'type' => 'VARCHAR',
               'constraint' => '500',
               'null' => true,
               'default'=> null
            ],
            'postal_address'=> [
                'type' => 'VARCHAR',
               'constraint' => '255',
               'null' => true,
               'default' => null
            ],
            'residential_address'=> [
                'type' => 'VARCHAR',
               'constraint' => '2000',
               'null' => true,
               'default' => null
            ],
            'hometown'=> [
                'type' => 'VARCHAR',
               'constraint' => '255',
               'null' => true,
               'default' => null
            ],
            'picture'=> [
                'type' => 'VARCHAR',
               'constraint' => '500',
               'null' => true,
               'default' => null
            ],
            'active'=> [
                'type' => 'INT',
               'constraint' => '11',
               'null' => false,
               'default' => 0
            ],
            'provisional_number'=> [
                'type' => 'VARCHAR',
               'constraint' => '50',
               'null' => true,
            ],
            'register_type'=> [
                'type' => 'VARCHAR',
               'constraint' => '50',
               'null' => true,
               'default'=> null
            ],
            'specialty'=> [
                'type' => 'VARCHAR',
               'constraint' => '255',
               'null' => true,
               'default' => null
            ],
            'category'=> [
                'type' => 'VARCHAR',
               'constraint' => '255',
               'null' => true,
               'default' => null
            ],
            'place_of_birth'=> [
                'type' => 'VARCHAR',
               'constraint' => '255',
               'null' => true,
               'default' => null
            ],
            'year_of_provisional'=> [
                'type' => 'DATE',
               'null' => true,
               'default' => null
            ],
            'year_of_permanent'=> [
                'type' => 'DATE',
               'null' => true,
               'default' => null
            ],
            'phone'=> [
                'type' => 'VARCHAR',
               'constraint' => '255',
               'null' => true,
               'default' => null
            ],
            'mailing_city'=> [
                'type' => 'VARCHAR',
               'constraint' => '255',
               'null' => true,
               'default' => null
            ],
            'mailing_region'=> [
                'type' => 'VARCHAR',
               'constraint' => '255',
               'null' => true,
               'default' => null
            ],
            'residential_city'=> [
                'type' => 'VARCHAR',
               'constraint' => '255',
               'null' => true,
               'default' => null
            ],
            'residential_region'=> [
                'type' => 'VARCHAR',
               'constraint' => '255',
               'null' => true,
               'default' => null
            ],
            'criminal_offense'=> [
                'type' => 'VARCHAR',
               'constraint' => '255',
               'null' => true,
               'default' => null
            ],
            'crime_details'=> [
                'type' => 'VARCHAR',
               'constraint' => '255',
               'null' => true,
               'default' => null
            ],
            'referee1_name'=> [
                'type' => 'VARCHAR',
               'constraint' => '255',
               'null' => true,
               'default' => null
            ],
            'referee1_phone'=> [
                'type' => 'VARCHAR',
               'constraint' => '255',
               'null' => true,
               'default' => null
            ],
            'referee1_email'=> [
                'type' => 'VARCHAR',
               'constraint' => '255',
               'null' => true,
               'default' => null
            ],
            'referee2_name'=> [
                'type' => 'VARCHAR',
               'constraint' => '255',
               'null' => true,
               'default' => null
            ],
            'referee2_phone'=> [
                'type' => 'VARCHAR',
               'constraint' => '255',
               'null' => true,
               'default' => null
            ],
            'referee2_email'=> [
                'type' => 'VARCHAR',
               'constraint' => '255',
               'null' => true,
               'default' => null
            ],
            'subspecialty'=> [
                'type' => 'VARCHAR',
               'constraint' => '255',
               'null' => true,
               'default' => null
            ],
            'institution_type'=> [
                'type' => 'VARCHAR',
               'constraint' => '255',
               'null' => true,
               'default' => null
            ],
            'region'=> [
                'type' => 'VARCHAR',
               'constraint' => '255',
               'null' => true,
               'default' => null
            ],
            'district'=> [
                'type' => 'VARCHAR',
               'constraint' => '255',
               'null' => true,
               'default' => null
            ],
            'town'=> [
                'type' => 'VARCHAR',
               'constraint' => '255',
               'null' => true,
               'default' => null
            ],
            'place_of_work'=> [
                'type' => 'VARCHAR',
               'constraint' => '255',
               'null' => true,
               'default' => null
            ],
            'portal_access'=> [
                'type' => 'VARCHAR',
               'constraint' => '255',
               'null' => true,
               'default' => null
            ],
            'intern_code'=> [
                'type' => 'VARCHAR',
               'constraint' => '255',
               'null' => true,
               'default' => null
            ],
            'practitioner_type'=> [
                'type' => 'ENUM',
                'constraint' => ['Doctor','Physician Assistant'],
               'null' => true,
               'default' => null
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


            // ... Add all other fields here ...
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
            'college_membership' => [
                'type' => 'ENUM',
                'constraint' => ['Member','Fellow'],
                'default' => null,
            ]
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('first_name', false);
        $this->forge->addKey('last_name', false);
        $this->forge->addKey('middle_name', false);
        $this->forge->addKey('intern_code', false);
        $this->forge->addKey('active', false);
        $this->forge->addKey('year_of_provisional', false);
        $this->forge->addKey('year_of_permanent', false);
        $this->forge->addKey('region', false);
        $this->forge->addKey('institution_type', false);
        $this->forge->addKey('district', false);
        $this->forge->addKey('place_of_work', false);
        $this->forge->addKey('portal_access', false);
        $this->forge->addKey('email', false);
        $this->forge->addKey('phone', false);

        $this->forge->createTable('practitioners', true, [
            'ENGINE' => 'InnoDB',
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('practitioners', true);
    }
}
