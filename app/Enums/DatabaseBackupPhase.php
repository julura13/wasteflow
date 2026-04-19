<?php

namespace App\Enums;

enum DatabaseBackupPhase
{
    case Started;
    case Succeeded;
    case Failed;
}
