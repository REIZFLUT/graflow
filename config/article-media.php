<?php

return [

    'disk' => env('ARTICLE_MEDIA_DISK', 'local'),

    'preview_max_width' => (int) env('ARTICLE_MEDIA_PREVIEW_MAX_WIDTH', 1600),

    'preview_webp_quality' => (int) env('ARTICLE_MEDIA_PREVIEW_WEBP_QUALITY', 82),

    'preview_jpeg_quality' => (int) env('ARTICLE_MEDIA_PREVIEW_JPEG_QUALITY', 85),

    'max_upload_size' => (int) env('ARTICLE_MEDIA_MAX_UPLOAD_SIZE', 10240),

    'allowed_mimes' => [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
    ],

    'staging_ttl_hours' => (int) env('ARTICLE_MEDIA_STAGING_TTL_HOURS', 24),

];
