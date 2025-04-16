<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUserTypeAnd extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('user_type', 'users')) {
            $this->forge->addColumn('users', [
                'user_type' => [
                    'type' => 'ENUM',
                    'null' => true,
                    'default' => null,
                    'constraint' => ['admin', 'license', 'cpd', 'student_index', 'guest', 'housemanship_facility', 'exam_candidate'],
                ],
            ]);
        }
        if (!$this->db->fieldExists('two_fa_deadline', 'users')) {
            $this->forge->addColumn('users', [
                'two_fa_deadline' => [
                    'type' => 'DATE',
                    'null' => true,
                    'default' => null,
                ],
            ]);
        }
        if (!$this->db->fieldExists('display_name', 'users')) {
            $this->forge->addColumn('users', [
                'display_name' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'default' => null,
                ],
            ]);
        }
        if (!$this->db->fieldExists('profile_table', 'users')) {
            $this->forge->addColumn('users', [
                'profile_table' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'default' => null,
                    'comment' => 'the table name where the details are stored. e.g. for licenses this would be the license table in the database, for cpd it would be the cpd_users table',
                ],
            ]);
        }
        if (!$this->db->fieldExists('profile_table_uuid', 'users')) {
            $this->forge->addColumn('users', [
                'profile_table_uuid' => [
                    'type' => 'CHAR',
                    'constraint' => 36,
                    'null' => true,
                    'default' => null,
                    'comment' => 'the uuid of the row in the profile table',
                ],
            ]);
        }
    }

    public function down()
    {
        //
    }
}
