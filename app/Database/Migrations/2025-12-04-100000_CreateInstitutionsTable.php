<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateInstitutionsTable extends Migration
{
    protected $tableName = "institutions";

    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'comment' => 'UUID primary key',
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'comment' => 'Institution name',
            ],
            'code' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'comment' => 'Unique institution code/identifier',
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'comment' => 'Contact email',
            ],
            'phone' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'comment' => 'Contact phone number',
            ],
            'address' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Physical address',
            ],
            'contact_person_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'comment' => 'Primary contact person name',
            ],
            'contact_person_email' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'comment' => 'Primary contact person email',
            ],
            'contact_person_phone' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'comment' => 'Primary contact person phone',
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Institution description/notes',
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['active', 'inactive', 'suspended'],
                'default' => 'active',
                'comment' => 'Institution status',
            ],
            'ip_whitelist' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'JSON array of allowed IP addresses/ranges',
            ],
            'created_by' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'comment' => 'User ID who created this institution',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('code');
        $this->forge->addKey('status');
        $this->forge->addKey('deleted_at');

        $this->forge->createTable($this->tableName, true, [
            'ENGINE' => 'InnoDB',
        ]);

        // Add trigger to auto-generate UUID
        $this->db->query("
            CREATE TRIGGER before_insert_institutions
            BEFORE INSERT ON {$this->tableName}
            FOR EACH ROW
            BEGIN
                IF NEW.id IS NULL OR NEW.id = '' THEN
                    SET NEW.id = UUID();
                END IF;
            END;
        ");
    }

    public function down()
    {
        $this->db->query("DROP TRIGGER IF EXISTS before_insert_institutions");
        $this->forge->dropTable($this->tableName, true);
    }
}
