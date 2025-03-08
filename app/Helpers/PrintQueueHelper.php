<?php
namespace App\Helpers;

use App\Models\PrintTemplateModel;
use App\Models\PrintQueueModel;
use App\Models\PrintQueueItemModel;
use App\Models\PrintHistoryModel;


class PrintQueueHelper
{
    protected $templateModel;
    protected $queueModel;
    protected $itemModel;
    protected $historyModel;

    public function __construct()
    {
        $this->templateModel = new PrintTemplateModel();
        $this->queueModel = new PrintQueueModel();
        $this->itemModel = new PrintQueueItemModel();
        $this->historyModel = new PrintHistoryModel();
    }

    /**
     * Create a new print queue with items
     * 
     * @param string $queueName The name of the queue
     * @param int $templateId The template to use
     * @param array $dataItems Array of objects to be printed
     * @param int $userId User creating the queue
     * @param int $priority Priority level (1-10)
     * @return int|bool Queue ID if successful, false otherwise
     */
    public function createQueue($queueName, $templateId, $dataItems, $userId, $priority = 5)
    {
        // Start transaction
        $db = \Config\Database::connect();
        $db->transStart();

        // Create queue
        $queueId = $this->queueModel->insert([
            'queue_name' => $queueName,
            'template_id' => $templateId,
            'created_by' => $userId,
            'priority' => $priority
        ]);

        // Add items to queue
        $order = 1;
        foreach ($dataItems as $data) {
            $this->itemModel->insert([
                'queue_id' => $queueId,
                'item_data' => json_encode($data),
                'print_order' => $order++
            ]);
        }

        $db->transComplete();

        return $db->transStatus() ? $queueId : false;
    }

    /**
     * Process a print queue
     * 
     * @param int $queueId The queue to process
     * @param int $userId User processing the queue
     * @return array Results of processing
     */
    public function processQueue($queueId, $userId)
    {
        // Get queue with template
        $queue = $this->queueModel->find($queueId);
        if (!$queue || $queue['status'] !== 'pending') {
            return ['success' => false, 'message' => 'Queue not found or not pending'];
        }

        // Update queue status
        $this->queueModel->update($queueId, ['status' => 'processing']);

        // Get template
        $template = $this->templateModel->find($queue['template_id']);
        if (!$template) {
            $this->queueModel->update($queueId, ['status' => 'failed']);
            return ['success' => false, 'message' => 'Template not found'];
        }

        // Get items to print
        $items = $this->itemModel->prepareForPrinting($queueId);

        $results = [
            'success' => true,
            'total' => count($items),
            'printed' => 0,
            'failed' => 0,
            'documents' => []
        ];

        // Process each item
        foreach ($items as $item) {
            try {
                // Parse the item data
                $itemData = json_decode($item['item_data'], true);

                // Here you would use your existing substitution system
                $printContent = $this->substituteVariables($template['template_content'], $itemData);

                // Add to results
                $results['documents'][] = $printContent;
                $results['printed']++;

                // Mark as printed
                $this->itemModel->markAsPrinted($item['item_id']);

                // Record in history
                $this->historyModel->recordPrint($queueId, $item['item_id'], $userId);
            } catch (\Exception $e) {
                // Log error
                $this->itemModel->update($item['item_id'], [
                    'status' => 'failed',
                    'error_message' => $e->getMessage()
                ]);

                // Record failure
                $this->historyModel->recordFailure($queueId, $item['item_id'], $userId, $e->getMessage());

                $results['failed']++;
            }
        }

        // Update queue status
        $newStatus = ($results['failed'] === 0) ? 'completed' :
            (($results['printed'] === 0) ? 'failed' : 'completed');
        $this->queueModel->update($queueId, ['status' => $newStatus]);

        $results['queue_status'] = $newStatus;
        return $results;
    }

    /**
     * Substitute variables in template
     * This is a placeholder for your existing substitution system
     * 
     * @param string $template The template with placeholders
     * @param array $data The data to substitute
     * @return string The processed content
     */
    private function substituteVariables($template, $data)
    {
        // This is where you would implement your existing substitution logic
        // For example:
        $templateEngineModel = new TemplateEngineHelper();
        $template = $templateEngineModel->process($template, $data);

        return $template;
    }
}