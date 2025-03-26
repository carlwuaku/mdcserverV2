<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SetApplicationFormsUuidNull extends Migration
{
    public function up()
    {
        //set the uuid field of the application_forms table to allow null values
        $fields = [
            'uuid' => [
                'type' => 'VARCHAR',
                'constraint' => 36,
                'null' => true,
                'default' => null
            ],
        ];
        $this->forge->modifyColumn('application_forms', $fields);
    }

    public function down()
    {
        //
    }
}
