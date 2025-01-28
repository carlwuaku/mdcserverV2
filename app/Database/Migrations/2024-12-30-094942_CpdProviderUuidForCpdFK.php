<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CpdProviderUuidForCpdFK extends Migration
{
    public function up()
    {
        //delete the foreign key for the provider_id
        $this->forge->dropForeignKey('cpd_topics', 'cpd_topics_provider_id_foreign');
        //delete the index for the provider_id
        $this->forge->dropKey('cpd_topics', 'cpd_topics_provider_id_foreign');



    }

    public function down()
    {
        //
    }
}
