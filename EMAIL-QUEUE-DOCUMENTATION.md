# Email Queue System Documentation

## Overview

The application now supports **asynchronous email sending** through a queue-based system. This allows emails to be queued and processed in the background, preventing delays in user-facing operations and providing better resilience when email services are temporarily unavailable.

## How It Works

### Synchronous vs Asynchronous Mode

The email system can operate in two modes:

1. **Synchronous Mode** (default): Emails are sent immediately when `EmailHelper::sendEmail()` is called
2. **Asynchronous Mode**: Emails are queued in the database and processed later by a cron job

## Configuration

### Environment Variables

Add these to your `.env` file:

```env
# Enable asynchronous email mode
EMAIL_ASYNC_MODE=true

# Optional: Secure the cron endpoint with a token
EMAIL_CRON_TOKEN=your_secure_random_token_here

# Existing email configuration
EMAIL_METHOD=brevo  # or smtp
BREVO_EMAIL_API_KEY=your_api_key_here
```

### Setting Up Async Mode

1. **Enable async mode** in `.env`:
   ```env
   EMAIL_ASYNC_MODE=true
   ```

2. **Set up a cron job** to process the queue regularly:
   ```bash
   # Process queue every 5 minutes
   */5 * * * * curl -X POST "https://yourdomain.com/cron/email/process-queue?cron_token=your_secure_random_token_here" >/dev/null 2>&1
   ```

3. **Optional: Secure the cron endpoint** by setting a token:
   ```env
   EMAIL_CRON_TOKEN=your_secure_random_token_here
   ```

## Usage

### Sending Emails

The existing email sending code **does not need to change**. The system automatically queues emails when async mode is enabled:

```php
use App\Helpers\EmailHelper;
use App\Helpers\EmailConfig;

// Create email configuration
$emailConfig = new EmailConfig(
    $message,      // Email body (HTML)
    $subject,      // Email subject
    $recipient,    // To email address
    $sender,       // From email address (optional)
    $cc,           // CC recipients (optional)
    $bcc,          // BCC recipients (optional)
    $attachments   // File paths array (optional)
);

// Send email (automatically queues if async mode is enabled)
EmailHelper::sendEmail($emailConfig);
```

### Forcing Synchronous Sending

If you need to send an email immediately even when async mode is enabled:

```php
EmailHelper::sendEmail($emailConfig, null, true); // Third parameter forces sync
```

### Manually Queueing Emails

You can explicitly queue emails with custom priority and scheduling:

```php
use App\Helpers\EmailHelper;
use App\Helpers\EmailConfig;

$emailConfig = new EmailConfig($message, $subject, $recipient);

// Queue with priority (1 = highest, 5 = lowest, default = 2)
$emailId = EmailHelper::queueEmail($emailConfig, $priority = 1);

// Queue with scheduled time
$scheduledTime = date('Y-m-d H:i:s', strtotime('+1 hour'));
$emailId = EmailHelper::queueEmail($emailConfig, $priority = 2, $scheduledTime);
```

## Cron Endpoint

### Endpoint Details

**URL**: `POST /cron/email/process-queue`

**Parameters**:
- `batch_size` (optional, integer): Number of emails to process in this run (default: 50)
- `cron_token` (optional, string): Security token if `EMAIL_CRON_TOKEN` is configured

**Response**:
```json
{
    "message": "Queue processed successfully",
    "stats": {
        "total": 50,
        "sent": 48,
        "failed": 2,
        "skipped": 0
    }
}
```

### Cron Job Examples

#### Using cURL (Most Common)

```bash
# Every 5 minutes
*/5 * * * * curl -X POST "https://yourdomain.com/cron/email/process-queue?cron_token=your_token" >/dev/null 2>&1

# Every 10 minutes with custom batch size
*/10 * * * * curl -X POST "https://yourdomain.com/cron/email/process-queue?cron_token=your_token&batch_size=100" >/dev/null 2>&1
```

#### Using wget

```bash
*/5 * * * * wget -q -O- "https://yourdomain.com/cron/email/process-queue?cron_token=your_token" >/dev/null 2>&1
```

#### Using PHP

```bash
*/5 * * * * php -r "file_get_contents('https://yourdomain.com/cron/email/process-queue?cron_token=your_token');" >/dev/null 2>&1
```

#### For Shared Hosting (cPanel, etc.)

Most shared hosting control panels provide a cron job interface. Set it up as:

- **Command**: `curl -X POST "https://yourdomain.com/cron/email/process-queue?cron_token=your_token"`
- **Interval**: Every 5 minutes (or as needed)

### Security Considerations

1. **Use a strong cron token**: Generate a random token:
   ```bash
   # On Linux/Mac
   openssl rand -hex 32

   # Or use an online generator
   # https://www.random.org/strings/
   ```

2. **Restrict by IP** (optional): If your cron service has a fixed IP, add IP validation in the controller:
   ```php
   // In EmailController::processQueue()
   $allowedIPs = ['YOUR_SERVER_IP', '127.0.0.1'];
   if (!in_array($this->request->getIPAddress(), $allowedIPs)) {
       return $this->respond(['message' => 'Unauthorized'], 401);
   }
   ```

3. **Monitor the endpoint**: Check logs regularly to ensure the cron is running successfully

## Database Structure

### email_queue Table

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT | Primary key |
| `to_email` | VARCHAR | Recipient email address |
| `from_email` | VARCHAR | Sender email address |
| `subject` | VARCHAR | Email subject |
| `message` | TEXT | Email body (HTML) |
| `cc` | VARCHAR | CC recipients (comma-separated) |
| `bcc` | VARCHAR | BCC recipients (comma-separated) |
| `attachment_path` | TEXT | JSON array of file paths |
| `status` | ENUM | pending, processing, sent, failed, cancelled |
| `priority` | INT | 1 (highest) to 5 (lowest) |
| `attempts` | INT | Number of send attempts |
| `max_attempts` | INT | Maximum retry attempts (default: 3) |
| `error_message` | TEXT | Error message if failed |
| `scheduled_at` | DATETIME | When to send (NULL = immediately) |
| `sent_at` | DATETIME | When successfully sent |
| `created_at` | DATETIME | When queued |
| `updated_at` | DATETIME | Last updated |

### email_queue_log Table

Tracks all status changes for audit purposes:

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT | Primary key |
| `email_queue_id` | INT | Foreign key to email_queue |
| `status` | VARCHAR | Status at this log entry |
| `notes` | TEXT | Additional information |
| `created_at` | DATETIME | When this status occurred |

## API Endpoints

### Send Email
**POST** `/email/send`

**Permission**: `Send_Email`

**Body**:
```json
{
    "subject": "Email Subject",
    "message": "Email body content",
    "receiver": "recipient@example.com",
    "sender": "sender@example.com",
    "cc": "cc@example.com",
    "bcc": "bcc@example.com",
    "priority": 1,
    "scheduled_at": "2025-12-11 14:00:00"
}
```

### Get Email Queue
**GET** `/email/queue`

**Permission**: `Send_Email`

**Query Parameters**:
- `status`: Filter by status (pending, sent, failed, etc.)
- `start_date`: Filter from date
- `end_date`: Filter to date
- `limit`: Results per page (default: 100)
- `page`: Page number (default: 0)

### Retry Failed Emails
**PUT** `/email/queue/retry`

**Permission**: `Send_Email`

**Body**:
```json
{
    "ids": [1, 2, 3]
}
```

### Cancel Pending Emails
**POST** `/email/cancel/{id}`

**Permission**: `Send_Email`

**Body**:
```json
{
    "ids": [1, 2, 3]
}
```

### Delete Emails from Queue
**DELETE** `/email/queue`

**Permission**: `Send_Email`

**Body**:
```json
{
    "ids": [1, 2, 3]
}
```

### Process Queue (Cron)
**POST** `/cron/email/process-queue`

**No authentication required** (secured by token)

**Query Parameters**:
- `cron_token`: Security token
- `batch_size`: Number to process (default: 50)

## Monitoring and Maintenance

### Check Queue Status

Query the database to see pending emails:

```sql
-- Count emails by status
SELECT status, COUNT(*) as count
FROM email_queue
GROUP BY status;

-- See emails that failed multiple times
SELECT * FROM email_queue
WHERE status = 'failed'
AND attempts >= max_attempts;

-- See recently queued emails
SELECT * FROM email_queue
ORDER BY created_at DESC
LIMIT 20;
```

### Common Issues and Solutions

#### Emails Stuck in "pending"

**Cause**: Cron job not running or misconfigured

**Solution**:
1. Check cron is set up correctly
2. Manually trigger: `curl -X POST "https://yourdomain.com/cron/email/process-queue?cron_token=your_token"`
3. Check application logs for errors

#### High Failure Rate

**Cause**: Email service issues, invalid configuration, or network problems

**Solution**:
1. Check `EMAIL_METHOD` is correctly set (brevo/smtp)
2. Verify API keys or SMTP credentials
3. Check `email_queue.error_message` for specific errors
4. Test email sending manually in development environment

#### Queue Growing Too Large

**Cause**: Processing speed slower than queue rate

**Solution**:
1. Increase cron frequency (e.g., every 2 minutes instead of 5)
2. Increase `batch_size` in cron call
3. Check for bottlenecks (slow email service, network issues)

### Cleanup Old Emails

Periodically clean up old sent emails to prevent database bloat:

```sql
-- Delete sent emails older than 30 days
DELETE FROM email_queue
WHERE status = 'sent'
AND sent_at < DATE_SUB(NOW(), INTERVAL 30 DAY);

-- Delete old failed emails (after reviewing)
DELETE FROM email_queue
WHERE status = 'failed'
AND attempts >= max_attempts
AND created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
```

Or create a maintenance endpoint/command to run periodically.

## Best Practices

### 1. Priority Levels

Use appropriate priorities for different email types:

- **Priority 1 (Highest)**: Critical transactional emails (password resets, verification codes)
- **Priority 2 (High)**: Important notifications (application status changes)
- **Priority 3 (Normal)**: Regular notifications
- **Priority 4 (Low)**: Bulk emails, newsletters
- **Priority 5 (Lowest)**: Marketing emails, reminders

### 2. Scheduled Emails

Schedule emails during off-peak hours:

```php
// Schedule for 2 AM next day
$scheduledTime = date('Y-m-d 02:00:00', strtotime('tomorrow'));
EmailHelper::queueEmail($emailConfig, 3, $scheduledTime);
```

### 3. Error Handling

Always wrap email sending in try-catch when you need to handle failures:

```php
try {
    EmailHelper::sendEmail($emailConfig);
} catch (\Throwable $e) {
    log_message('error', 'Failed to queue email: ' . $e->getMessage());
    // Implement fallback or notify admin
}
```

### 4. Testing

Test email queue in development:

```php
// In development, set async mode temporarily
putenv('EMAIL_ASYNC_MODE=true');

// Send test email
EmailHelper::sendEmail($emailConfig);

// Check it was queued
$queueModel = new \App\Models\EmailQueueModel();
$pending = $queueModel->getPendingEmails(10);
var_dump($pending);

// Process the queue manually
$stats = EmailHelper::processEmailQueue(10);
var_dump($stats);
```

## Migration from Sync to Async

### Step 1: Test in Development

1. Set `EMAIL_ASYNC_MODE=true` in development `.env`
2. Test email sending works correctly
3. Manually process queue to verify sending works
4. Check logs for any errors

### Step 2: Deploy with Async Disabled

1. Deploy code changes to production
2. Keep `EMAIL_ASYNC_MODE=false` or unset
3. Verify everything works as before

### Step 3: Enable Async Mode

1. Set `EMAIL_ASYNC_MODE=true` in production `.env`
2. Set up cron job
3. Monitor queue for first few hours
4. Check logs for errors

### Step 4: Fine-tune

1. Adjust cron frequency based on email volume
2. Adjust batch size based on processing time
3. Monitor server load

## Troubleshooting

### Cron Not Running

**Check**:
1. Cron service is enabled: `systemctl status cron` (Linux)
2. Cron logs: `grep CRON /var/log/syslog` (Linux)
3. Test endpoint manually: `curl -X POST "https://yourdomain.com/cron/email/process-queue"`

### Emails Not Being Queued

**Check**:
1. `EMAIL_ASYNC_MODE` is set to `true` in `.env`
2. Database migrations have run: `php spark migrate`
3. Application logs for errors

### Performance Issues

**If queue processing is slow**:
1. Increase cron frequency
2. Increase batch size
3. Consider upgrading email service plan
4. Check network latency to email service

**If application is slow**:
1. Ensure async mode is enabled
2. Check database indexes on `email_queue` table
3. Consider archiving/deleting old emails

## Support

For issues or questions:

1. Check application logs: `writable/logs/`
2. Check database for error messages: `SELECT * FROM email_queue WHERE status='failed'`
3. Review email queue logs: `SELECT * FROM email_queue_log ORDER BY created_at DESC LIMIT 100`
4. Test manually: Visit `/email/queue` endpoint (with proper authentication)

## Code References

- **Email Helper**: [app/Helpers/EmailHelper.php](app/Helpers/EmailHelper.php)
- **Email Queue Model**: [app/Models/EmailQueueModel.php](app/Models/EmailQueueModel.php)
- **Email Queue Log Model**: [app/Models/EmailQueueLogModel.php](app/Models/EmailQueueLogModel.php)
- **Email Controller**: [app/Controllers/EmailController.php](app/Controllers/EmailController.php)
- **Routes**: [app/Config/Routes.php:258-267](app/Config/Routes.php#L258-L267)

---

**Version**: 1.0
**Last Updated**: 2025-12-11
**Author**: Development Team
