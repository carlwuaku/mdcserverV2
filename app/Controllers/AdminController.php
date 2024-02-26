<?php

namespace App\Controllers;

use App\Models\SettingsModel;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;

class AdminController extends ResourceController
{
    
    public function getSetting($name = null){
        $settings = service("settings");
        $value = $settings->get($name);
        return $this->respond(['message' => '', 'data' => $value], ResponseInterface::HTTP_OK);
    }

    public function getSettings()
    {
        $settings = service("settings");
        try {
            $per_page = $this->request->getVar('limit') ? (int) $this->request->getVar('limit') : 1000;
            $page = $this->request->getVar('page') ? (int) $this->request->getVar('page') : 0;
            $withDeleted = $this->request->getVar('withDeleted') && $this->request->getVar('withDeleted') === "yes";
            $param = $this->request->getVar('param');
            $model = new SettingsModel();
            
            $builder = $param ? $model->search($param) : $model->builder();
            
            if ($withDeleted) {
                $model->withDeleted();
            }
            $totalBuilder = clone $builder;
            $total = $totalBuilder->countAllResults();
            $result = $builder->get($per_page, $page)->getResult();
            foreach ($result as  $value) {
                if($value->type !== 'string') {
                $value->value = unserialize($value->value);
                }
            }
            return $this->respond(['data' => $result, 'total' => $total,
                'displayColumns' => $model->getDisplayColumns()
            ], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message('error', __METHOD__ .''. $th->getMessage());
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function saveSetting(){
        $settings = service("settings");
        $name = $this->request->getVar("name");
        $value = $this->request->getVar("value");
        $settings->set($name, $value);
        return $this->respond(['message' => "Setting $name updated successfully", 'data' => null], ResponseInterface::HTTP_OK);
    }

    public function deleteSetting($name){
        $settings = service("settings");
        $settings->delete($name);
        return $this->respond(['message' => "Setting $name deleted successfully", 'data' => null], ResponseInterface::HTTP_OK);

    }
}
