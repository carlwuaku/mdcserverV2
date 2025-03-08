<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\PrintQueueItemModel;
use App\Models\PrintQueueModel;
use App\Models\PrintHistoryModel;
use App\Models\PrintTemplateModel;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use App\Models\ActivitiesModel;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;
class PrintQueueController extends ResourceController
{

    protected $printQueueModel;
    protected $printQueueItemModel;
    protected $printHistoryModel;
    protected $printTemplateModel;

    public function __construct()
    {
        $this->printQueueModel = new PrintQueueModel();
        $this->printQueueItemModel = new PrintQueueItemModel();
        $this->printHistoryModel = new PrintHistoryModel();
        $this->printTemplateModel = new PrintTemplateModel();
    }

    public function createPrintTemplate()
    {
        try {
            $rules = [
                "template_name" => "required|is_unique[print_templates.template_name]",
                "template_content" => "required",
            ];

            if (!$this->validate($rules)) {
                return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
            }
            $userId = auth()->id();
            $data = $this->request->getPost();
            $data['created_by'] = $userId;
            if (!$this->printTemplateModel->insert($data)) {
                return $this->respond(['message' => $this->printTemplateModel->errors()], ResponseInterface::HTTP_BAD_REQUEST);
            }
            $id = $this->printTemplateModel->getInsertID();
            /** @var ActivitiesModel $activitiesModel */
            $activitiesModel = new ActivitiesModel();
            $activitiesModel->logActivity("Created template {$data['template_name']}.", $userId, "printQueue");

            return $this->respond(['message' => 'Template created successfully', 'data' => $id], ResponseInterface::HTTP_OK);
        } catch (\Exception $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "An error occurred. Please try again"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function updatePrintTemplate($uuid)
    {
        try {
            $rules = [
                "template_name" => "required|is_unique[print_templates.template_name,uuid,$uuid]",
                "template_content" => "required",
            ];

            if (!$this->validate($rules)) {
                return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
            }
            $userId = auth()->id();
            $data = $this->request->getVar();
            $update = $this->printTemplateModel->builder()->where(['uuid' => $uuid])->update($data);
            if (!$update) {
                return $this->respond(['message' => $this->printTemplateModel->errors()], ResponseInterface::HTTP_BAD_REQUEST);
            }
            /** @var ActivitiesModel $activitiesModel */
            $activitiesModel = new ActivitiesModel();
            $activitiesModel->logActivity("Updated template {$data->template_name}.", $userId, "printQueue");

            return $this->respond(['message' => 'Template updated successfully'], ResponseInterface::HTTP_OK);
        } catch (\Exception $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "An error occurred. Please try again"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function deletePrintTemplate($uuid)
    {
        try {
            $userId = auth()->id();
            $delete = $this->printTemplateModel->builder()->where(['uuid' => $uuid])->delete();
            if (!$delete) {
                return $this->respond(['message' => $this->printTemplateModel->errors()], ResponseInterface::HTTP_BAD_REQUEST);
            }
            /** @var ActivitiesModel $activitiesModel */
            $activitiesModel = new ActivitiesModel();
            $activitiesModel->logActivity("Deleted template {$uuid}.", $userId, "printQueue");

            return $this->respond(['message' => 'Template deleted successfully'], ResponseInterface::HTTP_OK);
        } catch (\Exception $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "An error occurred. Please try again"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function getTemplate($uuid)
    {
        $model = new PrintTemplateModel();
         $builder = $model->builder();
        $data = $builder->where(["uuid" => $uuid])->get()->getRow();
        if (!$data) {
            return $this->respond("Template not found", ResponseInterface::HTTP_BAD_REQUEST);
        }
        return $this->respond(['data' => $data, 'displayColumns' => $model->getDisplayColumns()], ResponseInterface::HTTP_OK);
    }

    public function getTemplates()
    {
        try {
            $per_page = $this->request->getVar('limit') ? (int) $this->request->getVar('limit') : 100;
            $page = $this->request->getVar('page') ? (int) $this->request->getVar('page') : 0;
            $withDeleted = $this->request->getVar('withDeleted') && $this->request->getVar('withDeleted') === "yes";
            $param = $this->request->getVar('param');
            $sortBy = $this->request->getVar('sortBy') ?? "id";
            $sortOrder = $this->request->getVar('sortOrder') ?? "asc";
            $model = $this->printTemplateModel;
            /** if set, use this year for checking whether the license is in goodstanding */

            $builder = $param ? $model->search($param) : $model->builder();

            $builder->orderBy("$sortBy", $sortOrder);


            if ($withDeleted) {
                $model->withDeleted();
            }
            $totalBuilder = clone $builder;
            $total = $totalBuilder->countAllResults();
            $result = $builder->get($per_page, $page)->getResult();
            return $this->respond([
                'data' => $result,
                'total' => $total,
                'displayColumns' => $model->getDisplayColumns(),
                'columnFilters' => $model->getDisplayColumnFilters()
            ], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function docxToHtml()
    {
        $file = $this->request->getFile('docxFile');

        if (!$file || !$file->isValid()) {
            return $this->respond(['message' => 'No valid DOCX file was uploaded'], ResponseInterface::HTTP_BAD_REQUEST);
        }

        // Check file type
        if ($file->getClientMimeType() !== 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
            return $this->respond(['message' => 'Uploaded file is not a DOCX document'], ResponseInterface::HTTP_BAD_REQUEST);
        }

        try {
            // Move the uploaded file to a temporary location
            $tempPath = $file->getTempName();

            // Load the Word document
            $phpWord = IOFactory::load($tempPath);

            // Create a temporary file for the HTML output
            $htmlPath = WRITEPATH . 'uploads/' . $file->getRandomName() . '.html';

            // Make sure the directory exists
            if (!is_dir(dirname($htmlPath))) {
                mkdir(dirname($htmlPath), 0777, true);
            }

            // Convert to HTML
            $htmlWriter = IOFactory::createWriter($phpWord, 'HTML');
            $htmlWriter->save($htmlPath);

            // Read the generated HTML file
            $htmlContent = file_get_contents($htmlPath);

            // Remove the temporary HTML file
            unlink($htmlPath);
            return $this->respond([
                'data' => $htmlContent,
                
            ], ResponseInterface::HTTP_OK);

            

        } catch (\Exception $e) {
            log_message('error', 'DOCX to HTML conversion error: ' . $e->getMessage());
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);

            
        }
    }

    public function startPrintJob(){

    }


}
