<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAppSettingsOverridesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'setting_key' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'comment' => 'Dot notation key path (e.g., logo, licenseTypes.provisional.table)',
            ],
            'setting_value' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'JSON encoded value for the setting',
            ],
            'value_type' => [
                'type' => 'ENUM',
                'constraint' => ['string', 'number', 'boolean', 'array', 'object'],
                'default' => 'string',
                'comment' => 'Type of the value stored',
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Description of what this setting controls',
            ],
            'is_active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
                'comment' => '1 = override is active, 0 = use file value',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_by' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'updated_by' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('setting_key');
        $this->forge->createTable('app_settings_overrides');
    }

    public function down()
    {
        $this->forge->dropTable('app_settings_overrides');
    }
}
