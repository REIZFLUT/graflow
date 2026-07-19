<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Handbook Publication Name
    |--------------------------------------------------------------------------
    |
    | The name that identifies the singleton "Graflow Handbuch" publication.
    | The publication is owned by an administrator and describes the Graflow
    | software itself.
    |
    */

    'name' => env('HANDBOOK_NAME', 'Graflow Handbuch'),

    /*
    |--------------------------------------------------------------------------
    | Handbook Issue Label
    |--------------------------------------------------------------------------
    |
    | The handbook reuses the publication issue structure. All handbook
    | articles live inside a single implicit issue identified by this label.
    |
    */

    'issue_label' => env('HANDBOOK_ISSUE_LABEL', 'Handbuch'),

];
