<?php

return [
    'title' => 'Settings',
    'description' => 'Manage your profile and account settings',
    'aria_label' => 'Settings',

    'profile' => [
        'title' => 'Profile settings',
        'sr_title' => 'Profile settings',
        'heading' => 'Profile',
        'description' => 'Update your name and email address',
        'name' => 'Name',
        'name_placeholder' => 'Full name',
        'email' => 'Email address',
        'email_placeholder' => 'Email address',
        'unverified' => 'Your email address is unverified.',
        'resend_verification' => 'Click here to re-send the verification email.',
        'verification_sent' => 'A new verification link has been sent to your email address.',
        'save' => 'Save',
    ],

    'security' => [
        'title' => 'Security settings',
        'sr_title' => 'Security settings',
        'password_heading' => 'Update password',
        'password_description' => 'Ensure your account is using a long, random password to stay secure',
        'current_password' => 'Current password',
        'current_password_placeholder' => 'Current password',
        'new_password' => 'New password',
        'new_password_placeholder' => 'New password',
        'confirm_password' => 'Confirm password',
        'confirm_password_placeholder' => 'Confirm password',
        'save' => 'Save',
    ],

    'passkeys' => [
        'title' => 'Passkeys',
        'description' => 'Manage your passkeys for passwordless sign-in',
    ],

    'notifications' => [
        'title' => 'Notifications',
        'sr_title' => 'Notification settings',
        'heading' => 'Email notifications',
        'description' => 'Choose what you want to be notified about by email',
        'empty' => 'There are currently no notifications available for your role.',
        'save' => 'Save',
        'types' => [
            'assigned_responsible' => 'Notify me when I become responsible for an article (again).',
            'article_published' => 'Notify me when my article has been published.',
            'manuscript_submitted' => 'Notify me when a manuscript has been submitted.',
            'revision_requested' => 'Notify me when a revision has been requested.',
            'editorial_completed' => 'Notify me when editorial work has been completed.',
            'ready_for_publication' => 'Notify me when an article is ready for publication.',
        ],
    ],

    'appearance' => [
        'title' => 'Appearance settings',
        'sr_title' => 'Appearance settings',
        'description' => 'Update the appearance settings for your account',
        'light' => 'Light',
        'dark' => 'Dark',
        'system' => 'System',
    ],

    'delete_account' => [
        'title' => 'Delete account',
        'description' => 'Delete your account and all of its resources',
        'warning_title' => 'Warning',
        'warning_body' => 'Please proceed with caution, this cannot be undone.',
        'button' => 'Delete account',
        'confirm_title' => 'Are you sure you want to delete your account?',
        'confirm_description' => 'Once your account is deleted, all of its resources and data will also be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.',
        'password_placeholder' => 'Password',
    ],
];
