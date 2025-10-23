<?php
namespace App\Helpers\Enums;

enum InvoiceEvents: string
{
    case INVOICE_PAYMENT_COMPLETED = 'invoice_payment_completed';
    case INVOICE_PAYMENT_FILE_UPLOADED = 'invoice_payment_file_uploaded';

    case INVOICE_PAYMENT_FILE_DELETED = 'invoice_payment_file_deleted';
    case INVOICE_CREATED = 'invoice_created';
}