<?php

namespace App\Enum;

enum NotificationType: string
{
    case ALERT = 'alert';
    case WARNING = 'warning';
    case ERROR = 'error';
    case SUCCESS = "success";
}
