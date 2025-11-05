# Application Timeline - Quick Start Guide

## What Was Implemented

The application timeline system is now fully integrated and tracks:
- ✅ Status changes with before/after states
- ✅ User who made the change
- ✅ Stage configuration at time of change
- ✅ Actions executed during the transition
- ✅ Results of those actions (success/failure)
- ✅ Additional data submitted with the update
- ✅ Audit trail (IP address, user agent, timestamps)

## API Endpoints

### 1. Get Complete Timeline

```
GET /applications/details/{uuid}/timeline
GET /portal/applications/details/{uuid}/timeline
```

**Query Parameters:**
- `limit` (optional, default: 50, max: 200) - Number of entries to return
- `offset` (optional, default: 0) - Pagination offset
- `orderDir` (optional, default: DESC) - Sort direction (ASC or DESC)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "uuid": "timeline-uuid",
      "application_uuid": "app-uuid",
      "user_id": 123,
      "username": "admin@example.com",
      "from_status": "Pending",
      "to_status": "Approved",
      "stage_data": {
        "name": "Approved",
        "description": "Application approved",
        "actions": [...]
      },
      "actions_executed": [
        {"type": "send_email", "config": {...}},
        {"type": "create_license", "config": {...}}
      ],
      "actions_results": {
        "success": true,
        "actions": [
          {
            "action_type": "send_email",
            "success": true,
            "result": "Email sent",
            "timestamp": "2025-11-04 12:00:00"
          },
          {
            "action_type": "create_license",
            "success": true,
            "result": {"license_id": "ABC123"},
            "timestamp": "2025-11-04 12:00:01"
          }
        ]
      },
      "submitted_data": {
        "notes": "Approved after verification",
        "reviewer_comments": "All documents verified"
      },
      "notes": "Status updated successfully",
      "ip_address": "192.168.1.1",
      "user_agent": "Mozilla/5.0...",
      "created_at": "2025-11-04 12:00:00"
    }
  ],
  "total": 5,
  "limit": 50,
  "offset": 0
}
```

### 2. Get Simplified Status History

```
GET /applications/details/{uuid}/status-history
GET /portal/applications/details/{uuid}/status-history
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "to_status": "Pending",
      "created_at": "2025-11-01 10:00:00",
      "username": "admin@example.com"
    },
    {
      "to_status": "Under Review",
      "created_at": "2025-11-02 14:30:00",
      "username": "reviewer@example.com"
    },
    {
      "to_status": "Approved",
      "created_at": "2025-11-04 12:00:00",
      "username": "admin@example.com"
    }
  ]
}
```

### 3. Update Application Status (With Timeline Tracking)

```
PUT /applications/status
```

**Request Body:**
```json
{
  "form_type": "provisional_registration",
  "status": "Approved",
  "applicationIds": ["uuid1", "uuid2"],
  "notes": "Approved after verification",
  "comments": "All documents checked",
  "reviewer_comments": "Meets all requirements"
}
```

**New Fields (Optional):**
- `notes` - General notes about the status change
- `comments` - Additional comments
- `attachments` - Related attachments
- `reviewer_comments` - Specific reviewer feedback

## Automatic Timeline Logging

Every status update now automatically logs:

1. **When** - Timestamp of the change
2. **Who** - User ID and username
3. **What** - Status transition (from → to)
4. **How** - Actions executed and their results
5. **Why** - Submitted data, notes, comments
6. **Where** - IP address and user agent

## Example Usage

### Frontend (JavaScript)

```javascript
// Get timeline for an application
async function getApplicationTimeline(applicationUuid) {
  const response = await fetch(
    `/applications/details/${applicationUuid}/timeline?limit=50&orderDir=DESC`,
    {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
      }
    }
  );

  const data = await response.json();

  // Display timeline
  data.data.forEach(entry => {
    console.log(`${entry.created_at}: ${entry.from_status} → ${entry.to_status}`);
    console.log(`By: ${entry.username}`);
    console.log(`Actions:`, entry.actions_executed);
    console.log(`Results:`, entry.actions_results);
    if (entry.submitted_data) {
      console.log(`Notes:`, entry.submitted_data.notes);
    }
  });
}

// Update status with notes
async function updateApplicationStatus(formType, status, applicationIds, notes) {
  const response = await fetch('/applications/status', {
    method: 'PUT',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      form_type: formType,
      status: status,
      applicationIds: applicationIds,
      notes: notes,
      reviewer_comments: 'All requirements met'
    })
  });

  return await response.json();
}
```

### PHP (Backend Service)

```php
use App\Models\Applications\ApplicationTimelineModel;

$timelineModel = new ApplicationTimelineModel();

// Get full timeline
$timeline = $timelineModel->getApplicationTimeline($applicationUuid, [
    'limit' => 50,
    'orderDir' => 'DESC'
]);

// Get status history
$history = $timelineModel->getStatusHistory($applicationUuid);

// Get latest change
$latest = $timelineModel->getLatestStatusChange($applicationUuid);

// Create manual entry (if needed)
$timelineModel->createTimelineEntry(
    $applicationUuid,
    'Approved',
    [
        'fromStatus' => 'Pending',
        'userId' => auth()->user()->id,
        'submittedData' => ['notes' => 'Manual approval'],
        'notes' => 'Manually approved by admin'
    ]
);
```

## Database Schema

```sql
application_timeline
├── id (PK)
├── uuid (unique)
├── application_uuid (FK → application_forms.uuid)
├── user_id (FK → users.id)
├── from_status
├── to_status
├── stage_data (JSON)
├── actions_executed (JSON)
├── actions_results (JSON)
├── submitted_data (JSON)
├── notes
├── ip_address
├── user_agent
├── created_at
├── updated_at
└── deleted_at
```

## Permissions

Timeline endpoints use the same permission as viewing applications:
- **View Timeline**: `View_Application_Forms`

Both admin and portal users can view timelines for applications they have access to.

## Testing

Test the timeline by:

1. **Create an application**
2. **Update its status** with notes:
   ```json
   {
     "form_type": "test_form",
     "status": "Under Review",
     "applicationIds": ["app-uuid"],
     "notes": "Starting review process"
   }
   ```
3. **View the timeline**:
   ```
   GET /applications/details/app-uuid/timeline
   ```

## Troubleshooting

### Timeline entries not appearing?

Check:
1. Migration ran successfully: `php spark migrate`
2. Table exists: `php spark db:table application_timeline`
3. No PHP errors in logs: `tail -f writable/logs/*.log`

### Actions not being captured?

The `processStageActionsWithResults` method captures action results. Check:
1. Actions are defined in the stage
2. No exceptions during action execution
3. Check `actions_results` field in timeline entry

## Next Steps

Consider adding:
- Timeline export (PDF, CSV)
- Email notifications on status changes
- Timeline widget in admin dashboard
- Activity feed for users
- Timeline search and filtering
