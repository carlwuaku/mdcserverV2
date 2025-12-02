<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddInstitutionFieldsToUsers extends Migration
{
    public function up()
    {
        // Add institution_uuid field to users table
        if (!$this->db->fieldExists('institution_uuid', 'users')) {
            $this->forge->addColumn('users', [
                'institution_uuid' => [
                    'type' => 'CHAR',
                    'constraint' => 36,
                    'null' => true,
                    'default' => null,
                    'comment' => 'UUID of the institution (cpd_provider, housemanship_facility, or training_institution)',
                ],
            ]);
        }

        // Add institution_type field to users table
        if (!$this->db->fieldExists('institution_type', 'users')) {
            $this->forge->addColumn('users', [
                'institution_type' => [
                    'type' => 'ENUM',
                    'constraint' => ['cpd_provider', 'housemanship_facility', 'training_institution'],
                    'null' => true,
                    'default' => null,
                    'comment' => 'Type of institution this user belongs to',
                ],
            ]);
        }

        // Update user_type enum to include training_institution
        $sql = "ALTER TABLE `users` MODIFY COLUMN `user_type` ENUM('admin', 'license', 'cpd', 'student_index', 'guest', 'housemanship_facility', 'exam_candidate', 'training_institution') NULL DEFAULT NULL";
        $this->db->query($sql);
    }

    public function down()
    {
        // Remove the added columns
        if ($this->db->fieldExists('institution_uuid', 'users')) {
            $this->forge->dropColumn('users', 'institution_uuid');
        }

        if ($this->db->fieldExists('institution_type', 'users')) {
            $this->forge->dropColumn('users', 'institution_type');
        }

        // Revert user_type enum to original values
        $sql = "ALTER TABLE `users` MODIFY COLUMN `user_type` ENUM('admin', 'license', 'cpd', 'student_index', 'guest', 'housemanship_facility', 'exam_candidate') NULL DEFAULT NULL";
        $this->db->query($sql);
    }
}
