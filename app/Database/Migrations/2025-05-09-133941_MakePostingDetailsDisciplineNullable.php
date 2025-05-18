<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class MakePostingDetailsDisciplineNullable extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('housemanship_postings_application_details', [

            'discipline' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'default' => null
            ],
        ]);
    }

    public function down()
    {
        //
    }
}
