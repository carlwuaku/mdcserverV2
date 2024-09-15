<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFacilitiesTableKeys extends Migration
{
    public function up()
    {
        $this->forge->addKey('license_number', false, false, 'license_number_facilities');
        $this->forge->addKey('name', false, false, 'name_facilities');
        $this->forge->addKey('business_type', false, false, 'business_type_facilities');
        $this->forge->addKey('house_number', false, false, 'house_number_facilities');
        $this->forge->addKey('town', false, false, 'town_facilities');
        $this->forge->addKey('suburb', false, false, 'suburb_facilities');
        $this->forge->addForeignKey('license_number', 'licenses', 'license_number', 'CASCADE', 'CASCADE');
        $this->forge->processIndexes('facilities');
    }

    public function down()
    {
        //
    }
}
