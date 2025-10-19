<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Helpers\TemplateEngineHelper;
use App\Models\DocumentVerification\DocumentVerificationModel;
use App\Models\PrintQueueItemModel;
use App\Models\PrintQueueModel;
use App\Models\PrintHistoryModel;
use App\Models\PrintTemplateModel;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use App\Models\ActivitiesModel;
use Exception;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Print Queue API",
 *     description="API endpoints for managing print templates and queue operations"
 * )
 * @OA\Tag(
 *     name="Print Templates",
 *     description="Operations for managing print templates"
 * )
 * @OA\Tag(
 *     name="Print Queue",
 *     description="Operations for managing print queue and execution"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer"
 * )
 */
class PrintQueueController extends ResourceController
{

    protected $printQueueModel;
    protected $printQueueItemModel;
    protected $printHistoryModel;
    protected $printTemplateModel;
    protected $printTemplateRolesModel;

    public function __construct()
    {
        helper("auth");
        $this->printQueueModel = new PrintQueueModel();
        $this->printQueueItemModel = new PrintQueueItemModel();
        $this->printHistoryModel = new PrintHistoryModel();
        $this->printTemplateModel = new PrintTemplateModel();
        $this->printTemplateRolesModel = new \App\Models\PrintTemplateRolesModel();
    }

    /**
     * @OA\Post(
     *     path="/print-queue/templates/upload-docx",
     *     summary="Convert DOCX file to HTML",
     *     tags={"Print Templates"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="docxFile",
     *                     type="string",
     *                     format="binary",
     *                     description="DOCX file to convert"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="HTML content generated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="string", description="Generated HTML content")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid file or conversion error"
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/print-queue/templates",
     *     summary="Create a new print template",
     *     tags={"Print Templates"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"template_name", "template_content", "allowed_roles"},
     *             @OA\Property(property="template_name", type="string", example="Invoice Template"),
     *             @OA\Property(property="template_content", type="string", example="<html><body>Template content</body></html>"),
     *             @OA\Property(property="allowed_roles", type="array", @OA\Items(type="string"), example={"admin", "manager"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Template created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Template created successfully"),
     *             @OA\Property(property="data", type="string", format="uuid")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error or creation failed"
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
    public function createPrintTemplate()
    {
        try {
            $rules = [
                "template_name" => "required|is_unique[print_templates.template_name]",
                "template_content" => "required",
                "allowed_roles" => "required",
            ];

            if (!$this->validate($rules)) {
                return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
            }
            $userId = auth("tokens")->id();
            $data = $this->request->getVar();
            $allowedRoles = $data->allowed_roles;
            unset($data->allowed_roles);
            $data->created_by = $userId;

            $compiledQuery = $this->printTemplateModel->builder()->set($data)->getCompiledInsert();

            $this->printTemplateModel->db->transStart();

            if (!$this->printTemplateModel->insert($data)) {
                return $this->respond(['message' => $this->printTemplateModel->errors()], ResponseInterface::HTTP_BAD_REQUEST);
            }
            $templateUuid = $this->printTemplateModel->where('id', $this->printTemplateModel->getInsertID())->first()['uuid'];

            // Insert role permissions
            foreach ($allowedRoles as $role) {
                $this->printTemplateRolesModel->insert([
                    'template_uuid' => $templateUuid,
                    'role_name' => $role
                ]);
            }

            $this->printTemplateModel->db->transComplete();

            if ($this->printTemplateModel->db->transStatus() === false) {
                return $this->respond(['message' => 'Failed to create template with roles'], ResponseInterface::HTTP_BAD_REQUEST);
            }

            /** @var ActivitiesModel $activitiesModel */
            $activitiesModel = new ActivitiesModel();
            $activitiesModel->logActivity("Created template {$data->template_name}.", $userId, "printQueue");

            return $this->respond(['message' => 'Template created successfully', 'data' => $templateUuid], ResponseInterface::HTTP_OK);
        } catch (\Exception $th) {
            log_message("error", $th);
            return $this->respond(['message' => "An error occurred. Please try again"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @OA\Put(
     *     path="/print-queue/templates/{uuid}",
     *     summary="Update a print template",
     *     tags={"Print Templates"},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         description="UUID of the template to update",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"template_name", "template_content", "allowed_roles"},
     *             @OA\Property(property="template_name", type="string"),
     *             @OA\Property(property="template_content", type="string"),
     *             @OA\Property(property="allowed_roles", type="array", @OA\Items(type="string"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Template updated successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Template not found"
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
    public function updatePrintTemplate($uuid)
    {
        try {
            $rules = [
                "template_name" => "required|is_unique[print_templates.template_name,uuid,$uuid]",
                "template_content" => "required",
                "allowed_roles" => "required",
            ];

            if (!$this->validate($rules)) {
                return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
            }

            // Get the template
            $template = $this->printTemplateModel->builder()->where(['uuid' => $uuid])->get()->getRow();
            if (!$template) {
                return $this->respond(['message' => 'Template not found'], ResponseInterface::HTTP_NOT_FOUND);
            }

            // Check if user has access to this template
            $userRole = auth("tokens")->user()->role_name;
            //if user has the 'Create_Print_Templates' or 'Edit_Print_Templates' role, return all templates
            $rpModel = new \App\Models\RolePermissionsModel();
            if (
                !$rpModel->hasPermission(auth()->getUser()->role_name, 'Create_Print_Templates') && !$rpModel->hasPermission(auth()->getUser()->role_name, 'Edit_Print_Templates')
                && !$this->printTemplateRolesModel->hasAccess($template->uuid, $userRole)
            ) {
                return $this->respond(['message' => "Access denied"], ResponseInterface::HTTP_FORBIDDEN);
            }

            $userId = auth("tokens")->id();
            $data = $this->request->getVar();
            $allowedRoles = $data->allowed_roles;
            unset($data->allowed_roles);
            if (property_exists($data, "id")) {
                unset($data->id);
            }

            $this->printTemplateModel->db->transStart();

            // Update template
            $update = $this->printTemplateModel->builder()->where(['uuid' => $uuid])->update($data);
            if (!$update) {
                return $this->respond(['message' => $this->printTemplateModel->errors()], ResponseInterface::HTTP_BAD_REQUEST);
            }

            // Update roles
            $this->printTemplateRolesModel->where('template_uuid', $template->uuid)->delete();
            foreach ($allowedRoles as $role) {
                $this->printTemplateRolesModel->insert([
                    'template_uuid' => $template->uuid,
                    'role_name' => $role
                ]);
            }

            $this->printTemplateModel->db->transComplete();

            if ($this->printTemplateModel->db->transStatus() === false) {
                return $this->respond(['message' => 'Failed to update template roles'], ResponseInterface::HTTP_BAD_REQUEST);
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

    /**
     * @OA\Delete(
     *     path="/print-queue/templates/{uuid}",
     *     summary="Delete a print template",
     *     tags={"Print Templates"},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         description="UUID of the template to delete",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Template deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Template not found"
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
    public function deletePrintTemplate($uuid)
    {
        try {
            // Get the template
            $template = $this->printTemplateModel->builder()->where(['uuid' => $uuid])->get()->getRow();
            if (!$template) {
                return $this->respond(['message' => 'Template not found'], ResponseInterface::HTTP_NOT_FOUND);
            }

            // Check if user has access to this template
            $userRole = auth("tokens")->user()->role_name;
            if (!$this->printTemplateRolesModel->hasAccess($template->uuid, $userRole)) {
                return $this->respond(['message' => "Access denied"], ResponseInterface::HTTP_FORBIDDEN);
            }

            $userId = auth("tokens")->id();

            $this->printTemplateModel->db->transStart();

            // Delete template roles first
            $this->printTemplateRolesModel->where('template_uuid', $template->uuid)->delete();

            // Delete the template
            $delete = $this->printTemplateModel->builder()->where(['uuid' => $uuid])->delete();
            if (!$delete) {
                return $this->respond(['message' => $this->printTemplateModel->errors()], ResponseInterface::HTTP_BAD_REQUEST);
            }

            $this->printTemplateModel->db->transComplete();

            if ($this->printTemplateModel->db->transStatus() === false) {
                return $this->respond(['message' => 'Failed to delete template'], ResponseInterface::HTTP_BAD_REQUEST);
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

    /**
     * @OA\Get(
     *     path="/print-queue/templates/{uuid}",
     *     summary="Get a specific print template",
     *     tags={"Print Templates"},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         description="UUID of the template",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Template details",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="displayColumns", type="array", @OA\Items(type="string"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Template not found"
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
    public function getTemplate($uuid)
    {
        try {
            $model = new PrintTemplateModel();
            $builder = $model->builder();
            $template = $builder->where(["uuid" => $uuid])->get()->getRow();
            if (!$template) {
                return $this->respond("Template not found", ResponseInterface::HTTP_BAD_REQUEST);
            }

            // Check if user has access to this template
            $userRole = auth("tokens")->user()->role_name;
            //if user has the 'Create_Print_Templates' or 'Edit_Print_Templates' role, return all templates
            $rpModel = new \App\Models\RolePermissionsModel();
            if (!$rpModel->hasPermission(auth()->getUser()->role_name, 'Create_Print_Templates') && !$rpModel->hasPermission(auth()->getUser()->role_name, 'Edit_Print_Templates') && !$this->printTemplateRolesModel->hasAccess($template->uuid, $userRole)) {
                return $this->respond(['message' => "Access denied"], ResponseInterface::HTTP_FORBIDDEN);
            }

            // Get template roles
            $template->allowed_roles = array_column(
                $this->printTemplateRolesModel->where('template_uuid', $template->uuid)->findAll(),
                'role_name'
            );

            return $this->respond(['data' => $template, 'displayColumns' => $model->getDisplayColumns()], ResponseInterface::HTTP_OK);
        } catch (Exception $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "An error occurred"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @OA\Get(
     *     path="/print-queue/templates",
     *     summary="Get all accessible print templates",
     *     tags={"Print Templates"},
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=100)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", default=0)
     *     ),
     *     @OA\Parameter(
     *         name="param",
     *         in="query",
     *         description="Search parameter",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sortBy",
     *         in="query",
     *         description="Field to sort by",
     *         required=false,
     *         @OA\Schema(type="string", default="id")
     *     ),
     *     @OA\Parameter(
     *         name="sortOrder",
     *         in="query",
     *         description="Sort order (asc/desc)",
     *         required=false,
     *         @OA\Schema(type="string", default="asc")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of templates",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="total", type="integer"),
     *             @OA\Property(property="displayColumns", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="columnFilters", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
    public function getTemplates()
    {
        try {
            if (!auth("tokens")->loggedIn()) {
                return $this->respond(['message' => 'You are not logged in'], ResponseInterface::HTTP_UNAUTHORIZED);
            }

            $user = auth("tokens")->getUser();
            if (!$user) {
                return $this->respond(['message' => 'User not found'], ResponseInterface::HTTP_UNAUTHORIZED);
            }

            $per_page = $this->request->getVar('limit') ? (int) $this->request->getVar('limit') : 100;
            $page = $this->request->getVar('page') ? (int) $this->request->getVar('page') : 0;
            $withDeleted = $this->request->getVar('withDeleted') && $this->request->getVar('withDeleted') === "yes";
            $param = $this->request->getVar('param');
            $sortBy = $this->request->getVar('sortBy') ?? "id";
            $sortOrder = $this->request->getVar('sortOrder') ?? "asc";
            $model = $this->printTemplateModel;

            $userRole = $user->role_name;
            if (!$userRole) {
                return $this->respond(['message' => 'User role not found'], ResponseInterface::HTTP_FORBIDDEN);
            }
            //if user has the 'Create_Print_Templates' or 'Edit_Print_Templates' role, return all templates
            $rpModel = new \App\Models\RolePermissionsModel();
            if ($rpModel->hasPermission(auth()->getUser()->role_name, 'Create_Print_Templates') || $rpModel->hasPermission(auth()->getUser()->role_name, 'Edit_Print_Templates')) {
                $accessibleTemplateUuids = [ALL];
            } else {
                $accessibleTemplateUuids = $this->printTemplateRolesModel->getAccessibleTemplateUuids($userRole);
            }

            $builder = $param ? $model->search($param) : $model->builder();

            // Filter by accessible templates
            if (empty($accessibleTemplateUuids)) {
                // If user has no accessible templates, return empty result
                return $this->respond([
                    'data' => [],
                    'total' => 0,
                    'displayColumns' => $model->getDisplayColumns(),
                    'columnFilters' => $model->getDisplayColumnFilters()
                ], ResponseInterface::HTTP_OK);
            }
            if (!in_array(ALL, $accessibleTemplateUuids)) {
                $builder->whereIn('uuid', $accessibleTemplateUuids);
            }
            $builder->orderBy("$sortBy", $sortOrder);

            if ($withDeleted) {
                $model->withDeleted();
            }

            $totalBuilder = clone $builder;
            $total = $totalBuilder->countAllResults();
            $result = $builder->get($per_page, $page)->getResult();

            // Get roles for each template
            foreach ($result as $template) {
                $template->allowed_roles = array_column(
                    $this->printTemplateRolesModel->where('template_uuid', $template->uuid)->findAll(),
                    'role_name'
                );
            }

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

    /**
     * @OA\Post(
     *     path="/print-queue/templates/{uuid}/print-selection",
     *     summary="Execute template with provided data",
     *     tags={"Print Queue"},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         description="UUID of the template to execute",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"objects"},
     *             @OA\Property(
     *                 property="objects",
     *                 type="array",
     *                 @OA\Items(type="object", description="Data objects to merge with template")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Template executed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="string", description="Generated HTML content")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Template not found"
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
    public function execute($uuid)
    {
        try {
            $template = $this->printTemplateModel->builder()->where(["uuid" => $uuid])->get()->getRow();
            if (!$template) {
                return $this->respond(['message' => "Template not found"], ResponseInterface::HTTP_BAD_REQUEST);
            }
            $documentType = $this->request->getVar('document_type');
            if (!$documentType) {
                $documentType = $template->template_name;//THIS IS supposed to indicate the type of document. if not provided, it will be the name of the template
            }

            // Check if user has access to this template
            $userRole = auth("tokens")->user()->role_name;
            if (!$this->printTemplateRolesModel->hasAccess($template->uuid, $userRole)) {
                return $this->respond(['message' => "Access denied"], ResponseInterface::HTTP_FORBIDDEN);
            }

            $documentVerificationModel = new DocumentVerificationModel();
            $finishedTemplates = [];
            $data = $this->request->getVar();

            foreach ($data->objects as $object) {
                $templateEngine = new TemplateEngineHelper();
                $html = $templateEngine->process($template->template_content, $object);
                $document = [
                    "type" => $documentType,
                    "content" => $html,
                    "department" => auth('tokens')->user()->position ?? auth('tokens')->user()->role ?? "N/A",
                    "unique_id" => $object->unique_id ?? null,
                    "table_name" => $object->table_name ?? null,
                    "table_row_uuid" => $object->table_row_uuid ?? null
                ];
                $result = $documentVerificationModel->generateSecureDocument($document);
                //append the qr code to the content
                // $html .= "<img src='{$result['qr_path']}' alt='QR Code' />";
                $finishedTemplates[] = '<div class="page-break">' . $html . '</div>';
            }

            return $this->respond(['data' => implode("", $finishedTemplates)], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message('error', $th->getMessage());
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Create a new print queue item
     * 
     * @OA\Post(
     *     path="/print-queue",
     *     summary="Create a new print queue item",
     *     tags={"Print Queue"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"object", "template_name", "department", "table_name"},
     *             @OA\Property(property="object", type="object", example={"unique_id": "1234567890", "table_name": "users", "table_row_uuid": "1234567890"}),
     *             @OA\Property(property="template_name", type="string", example="Invoice Template"),
     *             @OA\Property(property="department", type="string", example="Accounting"),
     *             @OA\Property(property="table_name", type="string", example="users")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Print queue created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Print queue created successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request"
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
    public function createPrintQueue()
    {
        try {

            $data = $this->request->getVar();
            $this->printQueueModel->insert($data);
            return $this->respond(['message' => "Print queue created successfully"], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message('error', $th->getMessage());
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);

        }
    }

    /**
     * Summary of printDocuments
     * generate the verification token, qr code and print the document (return the html content or save to pdf and return the path).
     * the objects in the request data should contain the document_type, content, template, data, expiry_date(optional), table_name (optional: example, license_renewal or housemanship_posting), 
     * table_row_uuid(optional: the uuid for the row in the provided table), unique_id (optional: would be a license number or something similar). if content is empty, the template and data will be used to generate the content.
     * @return ResponseInterface
     */
    public function printDocuments()
    {
        try {
            /**
             * @var string $results
             */
            $results = "";
            /** @var array $documentData */
            $documentData = $this->request->getVar('data');//an array of objects.
            $documentVerificationModel = new DocumentVerificationModel();
            $templateEngine = new TemplateEngineHelper();
            foreach ($documentData as $document) {
                $document = (array) $document;
                $document['department'] = auth('tokens')->user()->position || auth('tokens')->user()->role_name; // Ensure department is set from the authenticated user
                if (!isset($document['type']) || empty($document['type'])) {
                    // return $this->respond(['message' => "Document type is required"], ResponseInterface::HTTP_BAD_REQUEST);
                    $document['type'] = "N/A";//THIS IS supposed to indicate the type of document. if not provided, it will be N/A
                }

                if (!isset($document['content']) || empty($document['content'])) {
                    if (!isset($document['template']) || empty($document['template'])) {
                        return $this->respond(['message' => "Template is required if the content is not provided"], ResponseInterface::HTTP_BAD_REQUEST);
                    }
                    if (!isset($document['data']) || empty($document['data'])) {
                        return $this->respond(['message' => "Data is required if the content is not provided"], ResponseInterface::HTTP_BAD_REQUEST);
                    }
                    $document['content'] = $templateEngine->process($document['template'], $document['data']);
                }
                $result = $documentVerificationModel->generateSecureDocument($document);
                //append the qr code to the content
                // $document['content'] .= "<img src='{$result['qr_path']}' alt='QR Code' />";
                $results .= '<div style="page-break-after: always;">' . $document['content'] . '</div>';
            }

            return $this->respond(['message' => "Print queue created successfully", 'data' => $results], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message('error', $th->getMessage());
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }


}
