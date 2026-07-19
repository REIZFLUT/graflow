<?php

return [
    'title' => 'Users',
    'description' => 'Manage user accounts and roles',
    'new' => 'New user',
    'empty' => 'No users have been created yet.',
    'create_first' => 'Create the first user',

    'table' => [
        'name' => 'Name',
        'email' => 'Email',
        'role' => 'Role',
        'created_at' => 'Created',
    ],

    'form' => [
        'name' => 'Name',
        'email' => 'Email',
        'password' => 'Password',
        'password_confirmation' => 'Confirm password',
        'password_optional' => 'Password (leave blank to keep current)',
        'role' => 'Role',
    ],

    'roles' => [
        'admin' => 'Administrator',
        'productmanager' => 'Product manager',
        'editor' => 'Editor',
        'lector' => 'Lector',
        'author' => 'Author',
    ],

    'create' => [
        'head_title' => 'New user',
        'title' => 'Create user',
        'description' => 'Create a new user account and assign a role.',
        'submit' => 'Create user',
    ],

    'edit' => [
        'description' => 'Update the user account, role, or password.',
        'breadcrumb' => 'Edit',
        'delete_heading' => 'Delete user',
        'delete_description' => 'The user account will be permanently deleted.',
        'delete_blocked' => 'This user cannot be deleted because related records still exist or they are the last administrator.',
    ],
];
