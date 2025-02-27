<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Helpers\EmailHelper;
use App\Helpers\EmailConfig;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\EmailQueueModel;
use App\Models\EmailQueueLogModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\CLI\CLI;
use App\Models\ActivitiesModel;

class EmailController extends ResourceController
{
   use ResponseTrait;

   protected $emailQueueModel;
   protected $emailQueueLogModel;

   public function __construct()
   {
      $this->emailQueueModel = new EmailQueueModel();
      $this->emailQueueLogModel = new EmailQueueLogModel();
   }


   public function send()
   {
      try {
         $subject = $this->request->getVar('subject');
         $message = $this->request->getVar('message');
         $email = $this->request->getVar('email');
         $sender = $this->request->getVar('sender');
         $receiver = $this->request->getVar('receiver');
         $cc = $this->request->getVar('cc');
         $bcc = $this->request->getVar('bcc');
         $attachment = $this->request->getVar('attachment');
         $priority = $this->request->getVar('priority') ?? 2; // Optional priority
         $scheduled = $this->request->getVar('scheduled_at'); // Optional scheduled time

         $emailConfig = new EmailConfig($message, $subject, $receiver, $sender, $cc, $bcc, $attachment);

         if (empty($subject)) {
            throw new \Exception('Subject is required');
         }
         if (empty($message)) {
            throw new \Exception('Message is required');
         }
         if (empty($receiver)) {
            throw new \Exception('Receiver is required');
         }
         if (empty($sender)) {
            throw new \Exception('Sender is required');
         }

         $emailId = EmailHelper::sendEmail($emailConfig);



         return $this->respond([
            'message' => 'Email sent successfully. Please check the email queue for status'
         ], ResponseInterface::HTTP_OK);

      } catch (\Throwable $th) {
         return $this->respond(['message' => $th->getMessage()], ResponseInterface::HTTP_BAD_REQUEST);
      }
   }

   // Endpoint to view email queue
   public function getQueue($status = null)
   {
      try {
         $model = $this->emailQueueModel;

         $per_page = $this->request->getVar('limit') ? (int) $this->request->getVar('limit') : 100;
         $page = $this->request->getVar('page') ? (int) $this->request->getVar('page') : 0;
         $withDeleted = $this->request->getVar('withDeleted') && $this->request->getVar('withDeleted') === "yes";
         $param = $this->request->getVar('param');
         $sortBy = $this->request->getVar('sortBy') ?? "id";
         $sortOrder = $this->request->getVar('sortOrder') ?? "asc";
         $from = $this->request->getGet('status');
         $start_date = $this->request->getGet('start_date');
         $end_date = $this->request->getGet('end_date');
         $cc = $this->request->getGet('cc');
         $bcc = $this->request->getGet('bcc');
         $status = $this->request->getGet('status');


         $builder = $param ? $model->search($param) : $model->builder();
         $builder->orderBy("$sortBy", $sortOrder);
         if ($from !== null) {
            $builder->where('from_email', $from);
         }
         if ($status !== null) {
            $builder->where('status', $status);
         }
         if ($start_date !== null) {
            $builder->where('created_at >=', $start_date);
         }
         if ($end_date !== null) {
            $builder->where('created_at <=', $end_date);
         }
         if ($cc !== null) {
            $builder->where('cc', $cc);
         }
         if ($bcc !== null) {
            $builder->where('bcc', $bcc);
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
         return $this->respond(['message' => $th->getMessage()], ResponseInterface::HTTP_BAD_REQUEST);
      }
   }

   public function countQueue($status = null)
   {
      try {
         $query = $this->emailQueueModel;

         if ($status) {
            $query = $query->where('status', $status);
         }

         $count = $query->countAllResults();

         return $this->respond([
            'data' => $count
         ], ResponseInterface::HTTP_OK);

      } catch (\Throwable $th) {
         return $this->respond(['message' => $th->getMessage()], ResponseInterface::HTTP_BAD_REQUEST);
      }
   }

   // Endpoint to retry a failed email
   public function retry()
   {
      try {
         $ids = $this->request->getVar('ids');
         if (!is_array($ids)) {
            throw new \Exception('Invalid request');
         }
         $emails = $this->emailQueueModel->whereIn('id', $ids)->findAll();
         foreach ($emails as $email) {
            EmailHelper::processQueuedEmail($email);
         }
         return $this->respond([
            'message' => 'Emails processed successfully. Please check the email queue for status'
         ], ResponseInterface::HTTP_OK);

      } catch (\Throwable $th) {
         return $this->respond(['message' => $th->getMessage()], ResponseInterface::HTTP_BAD_REQUEST);
      }
   }

   // Endpoint to cancel a pending email
   public function cancel($id)
   {
      try {
         $ids = $this->request->getVar('ids');
         //expect an array of numbers else throw an error
         if (!is_array($ids)) {
            throw new \Exception('Invalid request');
         }

         // Set status to cancelled
         $this->emailQueueModel->whereIn(
            'id',
            $ids
         )->update(null, [
                  'status' => 'cancelled'
               ]);

         // Log the cancellation
         $this->emailQueueLogModel->logStatusChange($id, 'cancelled', 'Manually cancelled');

         return $this->respond([
            'message' => 'Email cancelled'
         ], ResponseInterface::HTTP_OK);

      } catch (\Throwable $th) {
         return $this->respond(['message' => $th->getMessage()], ResponseInterface::HTTP_BAD_REQUEST);
      }
   }

   public function deleteQueueItem()
   {
      try {
         $ids = $this->request->getVar('ids');
         //expect an array of numbers else throw an error
         if (!is_array($ids)) {
            throw new \Exception('Invalid request');
         }
         // $emails = $this->emailQueueModel->whereIn('id', $ids)->findAll();
         $this->emailQueueModel->whereIn('id', $ids)->delete();

         // try {
         //    /** @var ActivitiesModel $activitiesModel */
         //    $activitiesModel = new ActivitiesModel();
         //    $activitiesModel->logActivity("Deleted email from queue for {$data['to_email']} with subject {$data['subject']}");
         // } catch (\Throwable $th) {
         //    log_message('error', $th->getMessage());
         // }


         return $this->respond(['message' => 'Emails deleted successfully'], ResponseInterface::HTTP_OK);
      } catch (\Throwable $th) {
         log_message('error', $th->getMessage());
         return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
      }
   }

}
