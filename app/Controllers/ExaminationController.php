<?php

namespace App\Controllers;

use App\Helpers\Utils;
use CodeIgniter\HTTP\ResponseInterface;
use App\Services\ExaminationService;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\Database\Exceptions\DatabaseException;


class ExaminationController extends ResourceController
{
    private ExaminationService $examinationService;
    public function __construct()
    {
        $this->examinationService = \Config\Services::examinationService();
    }

    public function createExamination()
    {
        try {
            $data = $this->request->getVar();
            $examModel = new \App\Models\Examinations\ExaminationsModel();
            /**
             * @var array
             */
            $letters = $this->request->getVar("letters");
            if (empty($letters) || !is_array($letters)) {
                throw new \InvalidArgumentException("Invalid letters data provided");
            }
            $lettersArray = array_map(function ($letter) {
                $letterObj = new \App\Helpers\Types\ExaminationLetterType(0, '', '', '', null, []);
                return $letterObj->createFromRequest($letter);
            }, $letters);

            //create the letters objects
            $result = $this->examinationService->createExam((array) $data, $lettersArray);

            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (\InvalidArgumentException $e) {
            return $this->respond(['message' => $e->getMessage()], ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateExamination($uuid)
    {
        try {
            $data = $this->request->getVar();
            $examModel = new \App\Models\Examinations\ExaminationsModel();
            /**
             * @var array
             */
            $letters = $this->request->getVar("letters");
            $lettersArray = null;
            if (!empty($letters) && is_array($letters)) {
                $lettersArray = array_map(function ($letter) {
                    $letterObj = new \App\Helpers\Types\ExaminationLetterType(0, '', '', '', null, []);
                    return $letterObj->createFromRequest($letter);
                }, $letters);
            }


            //create the letters objects
            $result = $this->examinationService->updateExam($uuid, (array) $data, $lettersArray);

            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (\InvalidArgumentException $e) {
            return $this->respond(['message' => $e->getMessage()], ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getExaminations()
    {
        try {
            $filters = $this->extractRequestFilters();
            $result = $this->examinationService->getAllExams($filters);

            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getExamination($uuid)
    {
        try {
            $result = $this->examinationService->getExamByUuid($uuid);
            $result->letters = $this->examinationService->getExaminationLetters($result->id);
            return $this->respond(["data" => $result], ResponseInterface::HTTP_OK);

        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getFormFields()
    {
        try {
            $fields = $this->examinationService->getExaminationForm();
            return $this->respond(['data' => $fields], ResponseInterface::HTTP_OK);

        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getExaminationApplications()
    {
        try {

            $filters = $this->extractRequestFilters();
            $result = $this->examinationService->getExamApplications($filters);

            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function getExaminationRegistrations()
    {
        try {
            $filters = $this->extractRequestFilters();
            $result = $this->examinationService->getExamRegistrations($filters);

            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function countExaminationApplications()
    {
        try {
            $filters = $this->extractRequestFilters();
            $total = $this->examinationService->countExamApplications($filters);

            return $this->respond(['data' => $total], ResponseInterface::HTTP_OK);

        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function createExaminationRegistrations()
    {
        try {
            $data = $this->request->getVar('data');
            if (empty($data) || !is_array($data)) {
                throw new \InvalidArgumentException("Invalid data provided");
            }

            //create the letters objects
            $result = $this->examinationService->createExamRegistration($data, auth()->id());

            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (\InvalidArgumentException $e) {
            log_message("error", $e);
            return $this->respond(['message' => $e->getMessage()], ResponseInterface::HTTP_BAD_REQUEST);
        } catch (DatabaseException $e) {
            log_message("error", $e);
            return $this->respond(['message' => Utils::getUserDatabaseErrorMessage($e->getMessage())], ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateExaminationRegistrations($uuid)
    {
        try {
            $data = $this->request->getVar('data');
            if (empty($data)) {
                throw new \InvalidArgumentException("Invalid data provided");
            }

            //create the letters objects
            $result = $this->examinationService->updateExamRegistration($uuid, (array) $data, auth()->id());

            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (\InvalidArgumentException $e) {
            log_message("error", $e);
            return $this->respond(['message' => $e->getMessage()], ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function setExaminationRegistrationResults()
    {
        try {
            /**
             * @var  object{uuid:string, index_number:string, result:string, scores:[]}[] $data
             */
            $data = $this->request->getVar('data');
            if (empty($data)) {
                throw new \InvalidArgumentException("Invalid data provided");
            }


            $result = $this->examinationService->setExaminationResults($data);

            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (\InvalidArgumentException $e) {
            log_message("error", $e);
            return $this->respond(['message' => $e->getMessage()], ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function removeExaminationResults($uuid)
    {
        try {



            $result = $this->examinationService->removeExaminationResults($uuid);

            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (\InvalidArgumentException $e) {
            log_message("error", $e);
            return $this->respond(['message' => $e->getMessage()], ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function publishExaminationRegistrationResults()
    {
        try {
            /**
             * @var  object{uuid:string, index_number:string, publish_result_date:string}[] $data
             */
            $data = $this->request->getVar('data');
            if (empty($data)) {
                throw new \InvalidArgumentException("Invalid data provided");
            }


            $result = $this->examinationService->publishResults($data);

            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (\InvalidArgumentException $e) {
            log_message("error", $e);
            return $this->respond(['message' => $e->getMessage()], ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function unpublishExaminationRegistrationResults()
    {
        try {
            /**
             * @var  object{uuid:string, index_number:string, publish_result_date:string}[] $data
             */
            $data = $this->request->getVar('data');
            if (empty($data)) {
                throw new \InvalidArgumentException("Invalid data provided");
            }


            $result = $this->examinationService->unpublishResults($data);

            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (\InvalidArgumentException $e) {
            log_message("error", $e);
            return $this->respond(['message' => $e->getMessage()], ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function deleteExaminationRegistration($uuid)
    {
        try {
            $result = $this->examinationService->deleteExaminationRegistration($uuid, auth()->id());

            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Returns the registration letter for a candidate.
     * 
     * @param string $uuid The UUID of the candidate
     * @return ResponseInterface
     */
    public function getCandidateRegistrationLetter($uuid)
    {
        try {
            $result = $this->examinationService->getCandidateLetter($uuid, "registration");

            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Returns the result letter for a candidate.
     * 
     * 
     * TODO: if the user is an admin, they can get any candidate's letter, even if the score is not published.
     * else, they can only get their own letter if the score is published.
     * 
     * @param string $uuid the uuid of the candidate
     * @return ResponseInterface
     */
    public function getCandidateResultLetter($uuid)
    {
        try {
            //TODO: if the user is an admin, they can get any candidate's letter, even if the score is not published.
            // else, they can only get their own letter if the score is published.

            $result = $this->examinationService->getCandidateLetter($uuid, "result");

            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (\InvalidArgumentException $e) {
            log_message("error", $e);
            return $this->respond(['message' => $e->getMessage()], ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getExaminationRegistrationResultCounts($examId)
    {
        try {
            $result = $this->examinationService->getExaminationRegistrationResultsCounts($examId);
            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateExaminationApplicationStatus()
    {
        try {
            /**
             * @var  array{id:string, intern_code:string, status:string} $data
             */
            $data = $this->request->getVar('data');
            if (empty($data)) {
                throw new \InvalidArgumentException("Invalid data provided");
            }


            $result = $this->examinationService->bulkUpdateExaminationApplications($data);

            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (\InvalidArgumentException $e) {
            log_message("error", $e);
            return $this->respond(['message' => $e->getMessage()], ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function deleteExaminationApplications()
    {
        try {
            /**
             * @var  string[] $data
             */
            $data = $this->request->getVar('data');
            if (empty($data)) {
                throw new \InvalidArgumentException("Invalid data provided");
            }

            $result = $this->examinationService->bulkDeleteExaminationApplications($data);

            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function downnloadExaminationApplicants($examId)
    {
        try {
            $fileDetails = $this->examinationService->getExaminationApplicationsInWord($examId);

            $filepath = $fileDetails['path'];
            $filename = $fileDetails['name'];
            $this->response->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
            $this->response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
            $this->response->setHeader('Content-Length', filesize($filepath));

            // Read and return file content
            $fileContent = file_get_contents($filepath);

            // Clean up temporary file
            unlink($filepath);

            return $this->response->setBody($fileContent);


        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

    }

    public function deleteExaminationApplication($uuid)
    {
        try {
            $result = $this->examinationService->deleteExaminationApplication($uuid);

            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function extractRequestFilters(): array
    {
        $filters = [];

        // Get common parameters
        $commonParams = [
            'limit',
            'page',
            'withDeleted',
            'param',
            'child_param',
            'sortBy',
            'sortOrder',
            'type',
            'exam_type',
            'exam_id',
            'created_on',
            'result'
        ];

        foreach ($commonParams as $param) {
            $value = $this->request->getVar($param);
            if ($value !== null) {
                $filters[$param] = $value;
            }
        }

        // Get all child_ and renewal_ parameters
        $allParams = $this->request->getVar();
        if (is_array($allParams)) {
            foreach ($allParams as $key => $value) {
                if (strpos($key, 'child_') === 0) {
                    $filters[$key] = $value;
                }
            }
        }

        return $filters;
    }
}
