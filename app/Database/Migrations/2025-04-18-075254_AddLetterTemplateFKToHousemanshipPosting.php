<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLetterTemplateFKToHousemanshipPosting extends Migration
{
    public function up()
    {
        $this->forge->addForeignKey('letter_template', 'print_templates', 'template_name', 'CASCADE', 'RESTRICT');
        $this->forge->processIndexes('housemanship_postings');
    }

    public function down()
    {
        //
    }
}
