<?php

return [
    'title' => 'Articles',
    'description' => 'Create and edit your articles',
    'new_article' => 'New article',
    'empty' => 'You have not created any articles yet.',
    'create_first' => 'Create your first article',

    'table' => [
        'title' => 'Title',
        'status' => 'Status',
        'publication' => 'Publication',
        'updated_at' => 'Last updated',
    ],

    'create' => [
        'head_title' => 'New article',
    ],

    'edit' => [
        'breadcrumb' => 'Edit',
    ],

    'metadata' => [
        'head_title' => 'Metadata – :title',
        'back_to_editor' => 'Back to editor',
        'title' => 'Metadata',
        'breadcrumb' => 'Metadata',

        'publication' => [
            'heading' => 'Publication',
            'description' => 'Assign the article to a publication and issue.',
            'label' => 'Publication',
            'placeholder' => 'Select publication',
        ],

        'issue' => [
            'label' => 'Issue',
            'placeholder' => 'Select issue',
            'placeholder_no_publication' => 'Select a publication first',
            'no_issues' => 'No issues have been created for this publication yet.',
            'manage_issues' => 'Manage issues',
        ],

        'categories' => [
            'heading' => 'Categories',
            'description' => 'Select one or more categories from the publication.',
            'select_first' => 'Categories can only be selected after choosing a publication and issue.',
            'no_categories' => 'No categories have been created for this publication yet.',
            'manage' => 'Manage categories',
            'placeholder' => 'Select categories',
            'search_placeholder' => 'Search category…',
            'empty' => 'No matching categories',
        ],

        'editor_settings' => [
            'heading' => 'Editor settings',
            'description' => 'Optionally override the default editor settings for this article.',
            'default' => 'Default:',
            'default_fallback' => 'App default (Spectral · with marginal column)',
            'use_default' => 'Use default',
            'create_set_hint' => 'Create an editor settings set first to choose an override.',
        ],
    ],

    'status' => [
        'draft' => 'Draft',
        'published' => 'Published',
        'archived' => 'Archived',
    ],

    'editor' => [
        'back' => 'Back',
        'footnotes' => 'Footnotes',
        'media' => 'Media',
        'metadata' => 'Metadata',
        'versions' => 'Versions',
        'save' => 'Save',
        'title_placeholder' => 'Untitled article',
        'versions_sheet' => 'Each save creates a new version.',
        'footnotes_sheet' => 'Overview of all footnotes in this article.',
        'media_sheet' => 'Manage and insert images for this article.',
        'image_in_use_alert' => 'This image is still used in the article and cannot be deleted.',
    ],

    'stats' => [
        'words' => ':count words',
        'letters' => ':count letters',
    ],

    'footnote' => [
        'add_title' => 'Add footnote',
        'edit_title' => 'Edit footnote',
        'reference' => 'Reference:',
        'select_text_first' => 'Select a word or passage in the article first.',
        'placeholder' => 'Footnote text…',
        'empty' => 'No footnotes yet.',
        'item_reference' => 'Reference: “:excerpt”',
    ],

    'media' => [
        'upload_title' => 'Upload image',
        'edit_title' => 'Edit image',
        'description' => 'Alt text and copyright are required. The caption is optional.',
        'file_label' => 'Image file',
        'alt_label' => 'Alt text',
        'alt_placeholder' => 'Description for screen readers…',
        'copyright_label' => 'Copyright',
        'copyright_placeholder' => 'e.g. Photo: Jane Doe',
        'caption_label' => 'Caption',
        'caption_placeholder' => 'Optional caption…',
        'validation_required' => 'Alt text and copyright are required fields.',
        'validation_no_file' => 'Please select an image file.',
        'empty' => 'No images have been uploaded for this article yet.',
        'upload_button' => 'Upload image',
        'used_in_article' => 'Used in article',
        'insert' => 'Insert',
        'error' => [
            'load_failed' => 'Media could not be loaded.',
            'upload_unavailable' => 'Upload unavailable.',
            'upload_failed' => 'Upload failed.',
            'save_metadata_failed' => 'Metadata could not be saved.',
            'delete_failed' => 'Media could not be deleted.',
        ],
    ],

    'versions' => [
        'empty' => 'No versions yet.',
        'label' => 'Version :number',
        'restore' => 'Restore',
        'restore_title' => 'Restore version :number?',
        'restore_description' => 'The current article will be replaced by this version. A new version will be created automatically.',
    ],

    'assignment' => [
        'with_publication' => ':publication – Issue :issue',
        'issue_only' => 'Issue :issue',
    ],

    'pdf' => [
        'generated_title' => 'PDF export – :title (:date)',
        'annotated_title' => 'Annotated PDF – :title (:date)',
        'export' => 'Export PDF',
        'exporting' => 'Generating PDF…',
        'export_failed' => 'PDF could not be generated.',
        'viewer_title' => 'PDF – :title',
        'back_to_editor' => 'Back to editor',
        'save_annotated' => 'Save annotated PDF',
        'saving_annotated' => 'Saving…',
        'loading_engine' => 'Loading PDF engine…',
        'tools' => [
            'highlight' => 'Highlight',
            'ink' => 'Pen',
            'square' => 'Rectangle',
            'delete' => 'Delete',
        ],
        'history' => 'PDF versions',
        'kind' => [
            'generated' => 'Generated',
            'annotated' => 'Annotated',
        ],
    ],
];
