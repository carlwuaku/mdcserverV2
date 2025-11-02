<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFileUploadsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'VARCHAR',
                'constraint' => '36',
            ],
            'filename' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => false,
            ],
            'original_filename' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'comment' => 'Original filename before system rename',
            ],
            'file_type' => [
                'type' => 'ENUM',
                'constraint' => ['practitioners_images', 'documents', 'applications', 'payments', 'qr_codes'],
                'null' => false,
            ],
            'mime_type' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ],
            'file_size' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => true,
                'comment' => 'File size in bytes',
            ],
            'uploaded_by' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => true,
                'comment' => 'User ID who uploaded the file',
            ],
            'related_entity_type' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
                'comment' => 'e.g., practitioner, application, invoice',
            ],
            'related_entity_id' => [
                'type' => 'VARCHAR',
                'constraint' => '36',
                'null' => true,
                'comment' => 'UUID of the related entity',
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['filename', 'file_type']);
        $this->forge->addKey('uploaded_by');
        $this->forge->addKey(['related_entity_type', 'related_entity_id']);
        $this->forge->addKey('created_at');

        $this->forge->createTable('file_uploads');

        // Add trigger to generate UUID for new records
        $sql = "
        CREATE TRIGGER before_insert_file_uploads
        BEFORE INSERT ON file_uploads
        FOR EACH ROW
        BEGIN
            IF NEW.id IS NULL OR NEW.id = '' THEN
                SET NEW.id = UUID();
            END IF;
            IF NEW.created_at IS NULL THEN
                SET NEW.created_at = CURRENT_TIMESTAMP;
            END IF;
        END;
        ";
        $this->db->query($sql);
    }

    public function down()
    {
        $this->db->query("DROP TRIGGER IF EXISTS before_insert_file_uploads");
        $this->forge->dropTable('file_uploads');
    }
}
