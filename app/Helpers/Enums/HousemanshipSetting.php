<?php
namespace App\Helpers\Enums;

enum HousemanshipSetting: string
{
    case AVAILABILITY_CATEGORIES = 'availabilityCategories';
    case SESSIONS = 'sessions';

    case APPLICATION_FORM_TAGS = 'applicationFormTags';
}