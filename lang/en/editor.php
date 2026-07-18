<?php

return [
    'settings_sets' => [
        'title' => 'Editor settings',
        'description' => 'Define reusable sets for font and marginal column',
        'new' => 'New set',
        'empty' => 'You have not created any editor settings yet.',
        'create_first' => 'Create your first set',
        'table' => [
            'name' => 'Name',
            'configuration' => 'Configuration',
            'publications' => 'Publications',
            'articles' => 'Articles',
        ],
        'create' => [
            'head_title' => 'New editor settings set',
            'title' => 'New set',
            'description' => 'Create a reusable set for the article editor',
            'submit' => 'Create set',
        ],
        'edit' => [
            'breadcrumb' => 'Edit',
        ],
        'delete_heading' => 'Delete set',
        'delete_description' => 'The set will be permanently removed.',
        'delete_in_use' => 'This set is used by :count publication(s) or article(s) and cannot be deleted.',
    ],

    'form' => [
        'name_placeholder' => 'Magazine Serif',
        'font_label' => 'Font',
        'font_placeholder' => 'Select font',
        'font' => [
            'spectral' => 'Spectral (Serif)',
            'roboto' => 'Roboto (Sans)',
        ],
        'marginal_column' => 'Show marginal column',
    ],

    'summary' => [
        'spectral' => 'Spectral',
        'roboto' => 'Roboto',
        'with_marginal' => 'with marginal column',
        'without_marginal' => 'without marginal column',
        'format' => ':font · :margin',
    ],

    'placeholder' => [
        'document' => 'Write your article content here…',
        'default' => 'Start writing…',
    ],

    'toolbar' => [
        'heading_2' => 'Heading 2',
        'heading_3' => 'Heading 3',
        'bold' => 'Bold',
        'italic' => 'Italic',
        'superscript' => 'Superscript',
        'subscript' => 'Subscript',
        'inline_math' => 'Formula',
        'block_math' => 'Block formula',
        'bullet_list' => 'Bullet list',
        'ordered_list' => 'Numbered list',
        'blockquote' => 'Quote',
        'paragraph_formats' => 'Paragraph formats',
        'character_formats' => 'Character formats',
        'block_elements' => 'Block elements',
        'marginal_note' => 'Marginal note',
        'footnote' => 'Footnote',
        'image' => 'Image',
        'remove_image' => 'Remove image from article',
        'table' => 'Table',
        'table_insert' => 'Insert table',
        'table_add_row_before' => 'Row above',
        'table_add_row_after' => 'Row below',
        'table_add_column_before' => 'Column left',
        'table_add_column_after' => 'Column right',
        'table_delete_row' => 'Delete row',
        'table_delete_column' => 'Delete column',
        'table_toggle_header_row' => 'Toggle header row',
        'table_delete' => 'Delete table',
        'spellcheck' => 'Check spelling',
    ],

    'spellcheck' => [
        'checking' => 'Checking…',
        'not_run' => 'Not checked yet. Start a check from the toolbar.',
        'empty' => 'No issues found.',
        'empty_document' => 'No text available to check.',
        'no_issues' => 'No spelling or grammar issues found.',
        'issues_found' => ':count issues found',
        'error' => 'Spell check failed.',
        'dismiss' => 'Dismiss',
        'apply' => 'Apply',
        'no_suggestions' => 'No suggestions',
    ],

    'format' => [
        'normal_paragraph' => [
            'label' => 'Normal paragraph',
            'description' => 'Standard paragraph without special formatting.',
        ],
        'normal_character' => [
            'label' => 'Normal text',
            'description' => 'Standard text without special character formatting.',
        ],
        'author_comment' => [
            'label' => 'Author comment',
            'description' => 'Author meta-comment. Display: italic and bold. Semantics: editorial addition outside the main text.',
        ],
        'red_text' => [
            'label' => 'Red text',
            'description' => 'Highlight important terms. Display: red font color. Semantics: special attention.',
        ],
        'info_box' => [
            'label' => 'Info box',
            'description' => 'Additional information for the reader. Display: light blue background with 1px blue border. Semantics: supplementary note.',
        ],
    ],

    'math' => [
        'insert_inline' => 'Insert formula',
        'insert_block' => 'Insert block formula',
        'edit_inline' => 'Edit formula',
        'edit_block' => 'Edit block formula',
        'description' => 'Use LaTeX syntax, e.g. :inline_example or :block_example.',
        'placeholder' => 'LaTeX formula…',
        'preview_empty' => 'Preview will appear here…',
    ],

    'marginal' => [
        'add_aria' => 'Add marginal note',
        'column_aria' => 'Marginal column',
    ],
];
