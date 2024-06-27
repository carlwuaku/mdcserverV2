<?php namespace App\Controllers;

use App\Controllers\BaseController;

class PortalsController extends BaseController
{
    
    public function managementPortal()
    {
        return view('portals/management_portal');
    }
}