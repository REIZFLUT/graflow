<?php

return [
    'title' => 'Publications',
    'description' => 'Manage magazines and their issues',
    'new' => 'New publication',
    'empty' => 'You have not created any publications yet.',
    'create_first' => 'Create your first publication',
    'view' => 'View',
    'owned_by' => 'Owner: :name',
    'owner_notice' => 'This publication belongs to :name. You can only view it.',

    'table' => [
        'name' => 'Name',
        'issues' => 'Issues',
        'owner' => 'Owner',
    ],

    'create' => [
        'description' => 'Create a publication, e.g. Energy Advisor Magazine',
        'name_placeholder' => 'Energy Advisor Magazine',
        'editor_settings_heading' => 'Editor settings',
        'editor_settings_description' => 'Choose the set for articles in this publication.',
        'no_sets_hint' => 'Create an editor settings set first.',
        'submit' => 'Create publication',
    ],

    'edit' => [
        'description' => 'Edit the publication, its issues, and categories',
        'readonly_description' => 'Overview of the publication, its issues, and categories',
        'editor_settings_description' => 'Choose a set for articles in this publication in the editor.',
        'no_editor_settings' => 'No editor settings set assigned.',
        'delete_heading' => 'Delete publication',
        'delete_description' => 'All issues and categories will also be deleted.',
    ],

    'issues' => [
        'heading' => 'Issues',
        'description' => 'Manage the issues of this publication, e.g. 07-2026.',
        'readonly_description' => 'Issues of this publication',
        'empty' => 'No issues created yet.',
        'table' => [
            'label' => 'Label',
            'actions' => 'Actions',
        ],
        'new_label' => 'New issue',
        'placeholder' => 'e.g. 07-2026',
        'add_button' => 'Add issue',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'delete' => 'Delete',
        'delete_title' => 'Delete issue?',
        'delete_description' => 'Articles assigned to this issue will lose their assignment.',
    ],

    'categories' => [
        'heading' => 'Categories',
        'description' => 'Define the categories that can be assigned to articles in this publication.',
        'readonly_description' => 'Categories of this publication',
        'empty' => 'No categories created yet.',
        'table' => [
            'name' => 'Name',
            'actions' => 'Actions',
        ],
        'new_label' => 'New category',
        'placeholder' => 'e.g. Market & Politics',
        'add_button' => 'Add category',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'delete' => 'Delete',
        'delete_title' => 'Delete category?',
        'delete_description' => 'The category will be removed from all assigned articles.',
    ],
];
