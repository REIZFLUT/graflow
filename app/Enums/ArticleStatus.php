<?php

namespace App\Enums;

enum ArticleStatus: string
{
    case Planned = 'planned';
    case Authoring = 'authoring';
    case ManuscriptSubmitted = 'manuscript_submitted';
    case ProductManagerCorrection = 'product_manager_correction';
    case RevisionRequested = 'revision_requested';
    case Revision = 'revision';
    case EditorialWork = 'editorial_work';
    case ReadyForPublication = 'ready_for_publication';
    case Published = 'published';
}
