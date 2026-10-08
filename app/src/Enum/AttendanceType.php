<?php

namespace App\Enum;

enum AttendanceType: string
{
    case PRESENT = "present";
    case ABSENT = "absent";
    case LATE = "late";
    case PENDING_PRESENT = "pending_present";
    case PENDING_ABSENT = "pending_absent";
    case PENDING_LATE = "pending_late";
}
