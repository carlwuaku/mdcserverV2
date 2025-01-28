<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCpdProviderUuidFK extends Migration
{
    public function up()
    {
        //add the foreign key for the provider_uuid
        $this->forge->addForeignKey('provider_uuid', 'cpd_providers', 'uuid', 'CASCADE', 'RESTRICT');
        $this->forge->processIndexes('cpd_topics');
    }

    public function down()
    {
        //
    }
}
