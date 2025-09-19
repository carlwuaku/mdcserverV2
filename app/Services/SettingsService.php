<?php

namespace App\Services;

use App\Models\SettingsModel;
use App\Helpers\Types\DataResponseType;

class SettingsService
{
    private SettingsModel $settingsModel;
    public function __construct()
    {
        $this->settingsModel = new SettingsModel();
    }


}