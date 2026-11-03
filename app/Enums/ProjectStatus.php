<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case Draft = 'draft';
    case Analyzing = 'analyzing';
    case Ready = 'ready';
    case Failed = 'failed';
}
