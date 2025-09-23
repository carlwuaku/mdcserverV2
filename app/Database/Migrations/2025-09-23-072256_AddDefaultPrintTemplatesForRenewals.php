<?php

namespace App\Database\Migrations;

use App\Helpers\Utils;
use CodeIgniter\Database\Migration;

class AddDefaultPrintTemplatesForRenewals extends Migration
{
    public function up()
    {
        //read from app-settings.json and add default print templates that are not already there
        $templates = Utils::getDefaultPrintTemplates();
        foreach ($templates as $template) {
            $data = [
                'template_name' => $template['template_name'],
                'template_content' => $template['template_content'],
                'active' => 1,
                'is_default' => 1
            ];

            $this->db->table('print_templates')->ignore(true)->insert($data);


        }
    }

    public function down()
    {
        //
    }
}
