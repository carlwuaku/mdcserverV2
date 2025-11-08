# Application Timeline Integration Guide

This guide shows how to integrate the application timeline tracking into your ApplicationService.

## Overview

The `application_timeline` table tracks:
- **Status changes**: From/to status transitions
- **User actions**: Who made the change
- **Stage configuration**: Complete stage data at time of change
- **Actions executed**: What actions were run
- **Action results**: Outcomes of those actions
- **Submitted data**: Any additional data submitted with the update
- **Audit trail**: IP address, user agent, timestamps

## Database Structure

```sql
application_timeline
├── id (INT, PK, auto_increment)
├── uuid (CHAR(36), unique)
├── application_uuid (CHAR(36), FK to application_forms)
├── user_id (INT, FK to users)
├── from_status (VARCHAR)
├── to_status (VARCHAR)
├── stage_data (JSON) - Complete stage configuration
├── actions_executed (JSON) - Array of actions that were run
├── actions_results (JSON) - Results from actions
├── submitted_data (JSON) - Additional data from the request
├── notes (TEXT)
├── ip_address (VARCHAR)
├── user_agent (TEXT)
├── created_at (TIMESTAMP)
├── updated_at (TIMESTAMP)
└── deleted_at (DATETIME)
```

## Integration Example

### Step 1: Run the Migration

```bash
php spark migrate
```

### Step 2: Update ApplicationService

Add timeline logging to the `updateApplicationStatus` method:

```php
<?php

namespace App\Services;

use App\Models\Applications\ApplicationTimelineModel;

class ApplicationService
{
    private ApplicationTimelineModel $timelineModel;

    public function __construct()
    {
        // ... existing code
        $this->timelineModel = new ApplicationTimelineModel();
    }

    /**
     * Update application status with timeline tracking
     */
    public function updateApplicationStatus(
        string $applicationType,
        string $status,
        array $applicationIds,
        int $userId,
        array $submittedData = []  // NEW: Accept additional data
    ): array {
        if (!$applicationType || !$status || !$applicationIds) {
            throw new \InvalidArgumentException("Application type, status, and application IDs are required");
        }

        // Get template and validate stage
        $template = ApplicationFormActionHelper::getApplicationTemplate($applicationType);
        if (!$template) {
            throw new \RuntimeException("Application template not found");
        }

        $stages = is_string($template->stages) ? json_decode($template->stages, true) : $template->stages;
        if (empty($stages)) {
            throw new \RuntimeException("Application stages not found");
        }

        $stage = $this->findStageByName($stages, $status);
        if (!$stage) {
            throw new \RuntimeException("Stage not found");
        }

        // Validate user permissions
        $this->validateUserPermissions($userId, $stage);

        // Get request metadata
        $request = \Config\Services::request();
        $ipAddress = $request->getIPAddress();
        $userAgent = $request->getUserAgent()->getAgentString();

        // Process applications
        $model = new ApplicationsModel();
        $applications = $model->builder()->whereIn('uuid', $applicationIds)->get()->getResult('array');

        $applicationIdsToUpdate = [];
        $applicationCodesArray = [];
        $timelineEntries = []; // NEW: Collect timeline entries

        foreach ($applications as $application) {
            $applicationCodesArray[] = $application['application_code'];
            $actionsResults = []; // NEW: Track action results

            if (!empty($stage['actions'])) {
                try {
                    // Process actions and capture results
                    $actionsResults = $this->processStageActionsWithResults(
                        $stage['actions'],
                        $application,
                        $model
                    );
                    $applicationIdsToUpdate[] = $application['uuid'];
                } catch (\Throwable $e) {
                    log_message('error', 'Stage action failed: ' . $e);

                    // NEW: Log failed timeline entry
                    $this->timelineModel->createTimelineEntry(
                        $application['uuid'],
                        $status,
                        [
                            'fromStatus' => $application['status'],
                            'userId' => $userId,
                            'stageData' => $stage,
                            'actionsExecuted' => $stage['actions'],
                            'actionsResults' => [
                                'success' => false,
                                'error' => $e->getMessage(),
                            ],
                            'submittedData' => $submittedData,
                            'notes' => 'Status update failed',
                            'ipAddress' => $ipAddress,
                            'userAgent' => $userAgent,
                        ]
                    );

                    throw new \RuntimeException("Failed to process application {$application['application_code']}: " . $e->getMessage());
                }
            } else {
                $applicationIdsToUpdate[] = $application['uuid'];
            }

            // NEW: Prepare timeline entry
            $timelineEntries[] = [
                'application_uuid' => $application['uuid'],
                'from_status' => $application['status'],
                'to_status' => $status,
                'stage_data' => $stage,
                'actions_executed' => $stage['actions'] ?? null,
                'actions_results' => $actionsResults,
                'submitted_data' => $submittedData,
                'user_id' => $userId,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ];
        }

        if (empty($applicationIdsToUpdate)) {
            throw new \RuntimeException("No applications were updated");
        }

        // Update status for successful applications
        $model->builder()->whereIn('uuid', $applicationIdsToUpdate)->update(['status' => $status]);

        // NEW: Insert timeline entries
        foreach ($timelineEntries as $entry) {
            $this->timelineModel->createTimelineEntry(
                $entry['application_uuid'],
                $entry['to_status'],
                [
                    'fromStatus' => $entry['from_status'],
                    'userId' => $entry['user_id'],
                    'stageData' => $entry['stage_data'],
                    'actionsExecuted' => $entry['actions_executed'],
                    'actionsResults' => $entry['actions_results'],
                    'submittedData' => $entry['submitted_data'],
                    'notes' => 'Status updated successfully',
                    'ipAddress' => $entry['ip_address'],
                    'userAgent' => $entry['user_agent'],
                ]
            );
        }

        $applicationCodes = implode(", ", $applicationCodesArray);
        $this->activitiesModel->logActivity("Updated applications {$applicationCodes} status to $status. See the logs for more details");

        return [
            'success' => true,
            'message' => 'Applications updated successfully. See logs for more details'
        ];
    }

    /**
     * Process stage actions and return results
     * NEW method to capture action results
     */
    private function processStageActionsWithResults(
        array $actions,
        array $application,
        ApplicationsModel $model
    ): array {
        $results = [];

        $model->db->transException(true)->transStart();

        try {
            $formData = json_decode($application['form_data'], true);
            $applicationData = array_merge($application, $formData);

            foreach ($actions as $index => $action) {
                $action = \App\Helpers\Types\ApplicationStageType::fromArray($action);

                try {
                    // Run the action
                    $actionResult = ApplicationFormActionHelper::runAction((object) $action, $applicationData);

                    // Capture result
                    $results[] = [
                        'action_type' => $action->type ?? 'unknown',
                        'action_config' => $action->config ?? null,
                        'success' => true,
                        'result' => $actionResult,
                        'timestamp' => date('Y-m-d H:i:s'),
                    ];
                } catch (\Throwable $e) {
                    $results[] = [
                        'action_type' => $action->type ?? 'unknown',
                        'action_config' => $action->config ?? null,
                        'success' => false,
                        'error' => $e->getMessage(),
                        'timestamp' => date('Y-m-d H:i:s'),
                    ];
                    throw $e; // Re-throw to trigger rollback
                }
            }

            $model->db->transComplete();

            return [
                'success' => true,
                'actions' => $results,
            ];
        } catch (\Throwable $e) {
            $model->db->transRollback();

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'actions' => $results,
            ];
        }
    }
}
```

### Step 3: Update Controller to Accept Submitted Data

```php
<?php

namespace App\Controllers;

class ApplicationsController extends BaseController
{
    public function updateApplicationStatus()
    {
        $applicationType = $this->request->getVar('application_type');
        $status = $this->request->getVar('status');
        $applicationIds = $this->request->getVar('application_ids');

        // NEW: Accept additional data from request
        $submittedData = [
            'notes' => $this->request->getVar('notes'),
            'attachments' => $this->request->getVar('attachments'),
            'comments' => $this->request->getVar('comments'),
            // Add any other fields you want to track
        ];

        // Remove null values
        $submittedData = array_filter($submittedData, fn($val) => $val !== null);

        try {
            $result = $this->applicationService->updateApplicationStatus(
                $applicationType,
                $status,
                $applicationIds,
                auth()->user()->id,
                $submittedData  // Pass submitted data
            );

            return $this->respond($result);
        } catch (\Throwable $e) {
            log_message('error', 'Status update error: ' . $e->getMessage());
            return $this->fail($e->getMessage(), 400);
        }
    }
}
```

### Step 4: Create Timeline Endpoint

Add endpoint to retrieve application timeline:

```php
<?php

// In ApplicationsController

/**
 * Get application timeline
 * GET /applications/details/{uuid}/timeline
 */
public function getApplicationTimeline(string $uuid)
{
    try {
        $timelineModel = new \App\Models\Applications\ApplicationTimelineModel();

        $limit = $this->request->getGet('limit') ?? 50;
        $offset = $this->request->getGet('offset') ?? 0;
        $orderDir = $this->request->getGet('orderDir') ?? 'DESC';

        $timeline = $timelineModel->getApplicationTimeline($uuid, [
            'limit' => $limit,
            'offset' => $offset,
            'orderDir' => $orderDir,
        ]);

        $total = $timelineModel->getTimelineCount($uuid);

        return $this->respond([
            'success' => true,
            'data' => $timeline,
            'total' => $total,
        ]);
    } catch (\Throwable $e) {
        log_message('error', 'Timeline fetch error: ' . $e->getMessage());
        return $this->fail($e->getMessage(), 400);
    }
}
```

### Step 5: Add Route

In `app/Config/Routes.php`:

```php
$routes->get(
    "applications/details/(:segment)/timeline",
    [ApplicationsController::class, "getApplicationTimeline/$1"],
    ["filter" => ["hasPermission:View_Application_Forms"]]
);
```

## Usage Examples

### Creating a Timeline Entry Manually

```php
$timelineModel = new ApplicationTimelineModel();

$timelineModel->createTimelineEntry(
    $applicationUuid,
    'Approved',
    [
        'fromStatus' => 'Pending',
        'userId' => auth()->user()->id,
        'stageData' => $stageConfiguration,
        'actionsExecuted' => [
            ['type' => 'send_email', 'config' => [...]],
            ['type' => 'create_license', 'config' => [...]],
        ],
        'actionsResults' => [
            'success' => true,
            'actions' => [
                ['action_type' => 'send_email', 'success' => true],
                ['action_type' => 'create_license', 'success' => true, 'license_id' => 'ABC123'],
            ]
        ],
        'submittedData' => [
            'notes' => 'Application approved after verification',
            'reviewer_comments' => 'All documents verified',
        ],
        'notes' => 'Approved by admin',
        'ipAddress' => $request->getIPAddress(),
        'userAgent' => $request->getUserAgent()->getAgentString(),
    ]
);
```

### Retrieving Timeline

```php
$timelineModel = new ApplicationTimelineModel();

// Get full timeline
$timeline = $timelineModel->getApplicationTimeline($applicationUuid);

// Get status history (simplified)
$statusHistory = $timelineModel->getStatusHistory($applicationUuid);

// Get latest status change
$latestChange = $timelineModel->getLatestStatusChange($applicationUuid);

// Get timeline by specific user
$userTimeline = $timelineModel->getTimelineByUser($userId);
```

## Frontend Display Example

```javascript
// Fetch timeline
fetch(`/applications/details/${applicationUuid}/timeline`)
  .then(res => res.json())
  .then(data => {
    data.data.forEach(entry => {
      console.log(`${entry.created_at}: ${entry.from_status} → ${entry.to_status} by ${entry.username}`);
      console.log('Actions executed:', entry.actions_executed);
      console.log('Results:', entry.actions_results);
      console.log('Submitted data:', entry.submitted_data);
    });
  });
```

## Benefits

1. **Complete Audit Trail**: Every status change is recorded with full context
2. **Action Tracking**: Know exactly what actions were executed and their results
3. **User Accountability**: Track who made changes and when
4. **Debugging**: Easily debug issues by reviewing the timeline
5. **Compliance**: Meet audit and compliance requirements
6. **User Experience**: Show users the progress of their application
7. **Analytics**: Analyze application processing times and bottlenecks

## Next Steps

1. Run the migration to create the table
2. Update your ApplicationService as shown
3. Add timeline endpoint to your routes
4. Build a timeline UI component in your frontend
5. Consider adding email notifications for status changes
6. Add timeline export functionality (PDF, CSV)
