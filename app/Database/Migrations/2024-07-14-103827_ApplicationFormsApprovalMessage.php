<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ApplicationFormsApprovalMessage extends Migration
{
    public function up()
    {
        $this->forge->addColumn('application_form_templates', [
            'on_approve_email_template' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => null,
            ],
            'on_deny_email_template' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => null,
            ],
            'approve_url' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => null,
            ],
            'deny_url' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => null,
            ]
        ]);
    }

    public function down()
    {
        //
    }
}
