<?php

namespace App\Enums;

enum ArticlePdfKind: string
{
    case Generated = 'generated';
    case Annotated = 'annotated';
}
