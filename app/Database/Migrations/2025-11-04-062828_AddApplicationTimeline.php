<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class AddApplicationTimeline extends Migration
{
    public function up()
    {
        // Create application_timeline table
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'uuid' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => false,
                'unique' => true,
            ],
            'application_uuid' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => false,
                'comment' => 'Foreign key to application_forms.uuid',
            ],
            'user_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'comment' => 'User who made the status change',
            ],
            'from_status' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'comment' => 'Previous status before change',
            ],
            'to_status' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
                'comment' => 'New status after change',
            ],
            'stage_data' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Complete stage configuration at time of change',
            ],
            'actions_executed' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Array of actions that were executed during this transition',
            ],
            'actions_results' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Results/outcomes from executing the actions',
            ],
            'submitted_data' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Any additional data submitted with the status update',
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Optional notes or comments about this status change',
            ],
            'ip_address' => [
                'type' => 'VARCHAR',
                'constraint' => 45,
                'null' => true,
                'comment' => 'IP address of the user who made the change',
            ],
            'user_agent' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Browser/client user agent',
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'default' => null,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
                'default' => new RawSql('CURRENT_TIMESTAMP'),
                'on_update' => new RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);

        // Add primary key
        $this->forge->addPrimaryKey('id');

        // Add indexes for better query performance
        $this->forge->addKey('application_uuid');
        $this->forge->addKey('user_id');
        $this->forge->addKey('to_status');
        $this->forge->addKey('created_at');
        $this->forge->addKey(['application_uuid', 'created_at']); // Composite index for timeline queries

        // Create the table
        $this->forge->createTable('application_timeline', true);

        // Add foreign key constraints
        $this->db->query('
            ALTER TABLE application_timeline
            ADD CONSTRAINT fk_application_timeline_application
            FOREIGN KEY (application_uuid) REFERENCES application_forms(uuid)
            ON DELETE CASCADE ON UPDATE CASCADE
        ');

        $this->db->query('
            ALTER TABLE application_timeline
            ADD CONSTRAINT fk_application_timeline_user
            FOREIGN KEY (user_id) REFERENCES users(id)
            ON DELETE SET NULL ON UPDATE CASCADE
        ');

        // Create UUID trigger
        $this->db->query("
            CREATE TRIGGER before_insert_application_timeline
            BEFORE INSERT ON application_timeline
            FOR EACH ROW
            BEGIN
                IF NEW.uuid IS NULL OR NEW.uuid = '' THEN
                    SET NEW.uuid = UUID();
                END IF;
            END
        ");
    }

    public function down()
    {
        // Drop trigger first
        $this->db->query('DROP TRIGGER IF EXISTS before_insert_application_timeline');

        // Drop foreign key constraints
        $this->db->query('ALTER TABLE application_timeline DROP FOREIGN KEY IF EXISTS fk_application_timeline_application');
        $this->db->query('ALTER TABLE application_timeline DROP FOREIGN KEY IF EXISTS fk_application_timeline_user');

        // Drop the table
        $this->forge->dropTable('application_timeline', true);
    }
}
