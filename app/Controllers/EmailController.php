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

/**
 * @OA\Tag(
 *     name="Email",
 *     description="Operations for managing email queue and sending emails"
 * )
 */
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

   /**
    * @OA\Post(
    *     path="/email/send",
    *     summary="Send an email",
    *     description="Queue an email for sending with optional scheduling and attachments",
    *     tags={"Email"},
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\JsonContent(
    *             required={"subject", "message", "email", "sender", "receiver"},
    *             @OA\Property(property="subject", type="string", description="Email subject"),
    *             @OA\Property(property="message", type="string", description="Email body content"),
    *             @OA\Property(property="email", type="string", format="email", description="Sender's email address"),
    *             @OA\Property(property="sender", type="string", description="Sender's name"),
    *             @OA\Property(property="receiver", type="string", description="Recipient's email address"),
    *             @OA\Property(property="cc", type="string", description="CC recipients", nullable=true),
    *             @OA\Property(property="bcc", type="string", description="BCC recipients", nullable=true),
    *             @OA\Property(property="attachment", type="string", description="File attachment path", nullable=true),
    *             @OA\Property(property="priority", type="integer", description="Email priority (1-5)", default=2),
    *             @OA\Property(property="scheduled_at", type="string", format="date-time", description="Schedule time for sending", nullable=true)
    *         )
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Email queued successfully"
    *     ),
    *     @OA\Response(
    *         response=400,
    *         description="Validation error"
    *     ),
    *     security={{"bearerAuth": {}}}
    * )
    */
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

   /**
    * @OA\Get(
    *     path="/email/queue",
    *     summary="Get email queue",
    *     description="Retrieve list of emails in the queue with optional filtering",
    *     tags={"Email"},
    *     @OA\Parameter(
    *         name="status",
    *         in="query",
    *         description="Filter by email status",
    *         required=false,
    *         @OA\Schema(type="string")
    *     ),
    *     @OA\Parameter(
    *         name="start_date",
    *         in="query",
    *         description="Filter by start date",
    *         required=false,
    *         @OA\Schema(type="string", format="date")
    *     ),
    *     @OA\Parameter(
    *         name="end_date",
    *         in="query",
    *         description="Filter by end date",
    *         required=false,
    *         @OA\Schema(type="string", format="date")
    *     ),
    *     @OA\Parameter(
    *         name="cc",
    *         in="query",
    *         description="Filter by CC recipient",
    *         required=false,
    *         @OA\Schema(type="string")
    *     ),
    *     @OA\Parameter(
    *         name="bcc",
    *         in="query",
    *         description="Filter by BCC recipient",
    *         required=false,
    *         @OA\Schema(type="string")
    *     ),
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
    *     @OA\Response(
    *         response=200,
    *         description="List of queued emails",
    *         @OA\JsonContent(
    *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
    *             @OA\Property(property="total", type="integer"),
    *             @OA\Property(property="displayColumns", type="array", @OA\Items(type="string")),
    *             @OA\Property(property="columnFilters", type="object")
    *         )
    *     ),
    *     security={{"bearerAuth": {}}}
    * )
    */
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

   /**
    * @OA\Get(
    *     path="/email/queue/count",
    *     summary="Count emails in queue",
    *     description="Get the total count of emails in queue, optionally filtered by status",
    *     tags={"Email"},
    *     @OA\Parameter(
    *         name="status",
    *         in="query",
    *         description="Filter by email status",
    *         required=false,
    *         @OA\Schema(type="string")
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Email count",
    *         @OA\JsonContent(
    *             @OA\Property(property="data", type="integer")
    *         )
    *     ),
    *     security={{"bearerAuth": {}}}
    * )
    */
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

   /**
    * @OA\Post(
    *     path="/email/retry",
    *     summary="Retry failed emails",
    *     description="Attempt to resend failed emails from the queue",
    *     tags={"Email"},
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\JsonContent(
    *             required={"ids"},
    *             @OA\Property(
    *                 property="ids",
    *                 type="array",
    *                 @OA\Items(type="integer"),
    *                 description="Array of email queue IDs to retry"
    *             )
    *         )
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Emails processed successfully"
    *     ),
    *     @OA\Response(
    *         response=400,
    *         description="Invalid request"
    *     ),
    *     security={{"bearerAuth": {}}}
    * )
    */
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

   /**
    * @OA\Post(
    *     path="/email/cancel/{id}",
    *     summary="Cancel pending emails",
    *     description="Cancel pending emails in the queue",
    *     tags={"Email"},
    *     @OA\Parameter(
    *         name="id",
    *         in="path",
    *         required=true,
    *         @OA\Schema(type="integer")
    *     ),
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\JsonContent(
    *             required={"ids"},
    *             @OA\Property(
    *                 property="ids",
    *                 type="array",
    *                 @OA\Items(type="integer"),
    *                 description="Array of email queue IDs to cancel"
    *             )
    *         )
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Emails cancelled successfully"
    *     ),
    *     @OA\Response(
    *         response=400,
    *         description="Invalid request"
    *     ),
    *     security={{"bearerAuth": {}}}
    * )
    */
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

   /**
    * @OA\Delete(
    *     path="/email/queue",
    *     summary="Delete emails from queue",
    *     description="Permanently delete emails from the queue",
    *     tags={"Email"},
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\JsonContent(
    *             required={"ids"},
    *             @OA\Property(
    *                 property="ids",
    *                 type="array",
    *                 @OA\Items(type="integer"),
    *                 description="Array of email queue IDs to delete"
    *             )
    *         )
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Emails deleted successfully"
    *     ),
    *     @OA\Response(
    *         response=400,
    *         description="Invalid request"
    *     ),
    *     security={{"bearerAuth": {}}}
    * )
    */
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
