<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCpdProviderUuidToCpd extends Migration
{
    public function up()
    {
        //create the provider_uuid column
        if (!$this->db->fieldExists('provider_uuid', 'cpd_topics')) {
            $this->forge->addColumn('cpd_topics', [
                'provider_uuid' => [
                    'type' => 'VARCHAR',
                    'constraint' => 36,
                    'null' => true,
                ],
            ]);
        }

        // get the provider_uuid from the cpd_providers table using the provider_id
        $this->db->query("update cpd_topics JOIN cpd_providers ON cpd_topics.provider_id = cpd_providers.id SET cpd_topics.provider_uuid = cpd_providers.uuid");
    }

    public function down()
    {
        //
    }
}
