<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMergeStrategyToAppSettingsOverrides extends Migration
{
    public function up()
    {
        $fields = [
            'merge_strategy' => [
                'type' => 'ENUM',
                'constraint' => ['replace', 'merge', 'append', 'prepend'],
                'default' => 'replace',
                'null' => false,
                'comment' => 'How to merge arrays/objects: replace=full override, merge=combine with file, append=add to end, prepend=add to start',
                'after' => 'value_type'
            ]
        ];

        $this->forge->addColumn('app_settings_overrides', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('app_settings_overrides', 'merge_strategy');
    }
}
