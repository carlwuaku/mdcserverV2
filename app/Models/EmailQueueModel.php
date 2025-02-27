<?php

namespace App\Models;

use App\Helpers\Interfaces\TableDisplayInterface;
use App\Models\MyBaseModel;

class EmailQueueModel extends MyBaseModel implements TableDisplayInterface
{
    protected $table = 'email_queue';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'to_email',
        'from_email',
        'subject',
        'message',
        'cc',
        'bcc',
        'attachment_path',
        'status',
        'priority',
        'attempts',
        'max_attempts',
        'error_message',
        'headers',
        'scheduled_at',
        'sent_at'
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    // Validation
    protected $validationRules = [];
    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = [];
    protected $afterInsert = [];
    protected $beforeUpdate = [];
    protected $afterUpdate = [];
    protected $beforeFind = [];
    protected $afterFind = [];
    protected $beforeDelete = [];
    protected $afterDelete = [];

    public function queueEmail($data)
    {
        return $this->insert($data);
    }

    // Get pending emails for processing
    public function getPendingEmails($limit = 50)
    {
        return $this->where('status', 'pending')
            ->where('scheduled_at <=', date('Y-m-d H:i:s'))
            ->orWhere('scheduled_at', null)
            ->orderBy('priority', 'ASC')
            ->orderBy('created_at', 'ASC')
            ->limit($limit)
            ->find();
    }

    // Update email status
    public function updateStatus($id, $status, $errorMessage = null)
    {
        $data = ['status' => $status];

        if ($status === 'sent') {
            $data['sent_at'] = date('Y-m-d H:i:s');
        }

        if ($status === 'failed') {
            $data['attempts'] = $this->incrementAttempts($id);
            $data['error_message'] = $errorMessage;

            // If max attempts reached, mark as permanently failed
            $email = $this->find($id);
            if ($email && $data['attempts'] >= $email['max_attempts']) {
                $data['status'] = 'failed';
            } else {
                $data['status'] = 'pending'; // Reset to pending for retry
            }
        }

        return $this->update($id, $data);
    }

    // Increment attempt count
    private function incrementAttempts($id)
    {
        $email = $this->find($id);
        if (!$email) {
            return 1;
        }

        return $email['attempts'] + 1;
    }

    // Get failed emails that can be retried
    public function getRetryableEmails()
    {
        return $this->where('status', 'failed')
            ->where('attempts <', 'max_attempts', false)
            ->find();
    }

    public $searchFields = [
        'subject',
        'message'
    ];

    public function getDisplayColumns(): array
    {
        return [
            'to_email',
            'from_email',
            'subject',
            'message',
            'cc',
            'bcc',
            'attachment_path',
            'status',
            'priority',
            'attempts',
            'max_attempts',
            'error_message',
            'headers',
            'scheduled_at',
            'sent_at'
        ];
    }

    public function getDisplayColumnLabels(): array
    {
        return [];
    }

    public function getDisplayColumnFilters(): array
    {
        return [];
    }

}
