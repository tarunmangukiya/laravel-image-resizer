<?php

return array(

    /*
    |--------------------------------------------------------------------------
    | Image Driver
    |--------------------------------------------------------------------------
    |
    | Intervention Image supports "GD Library" and "Imagick" to process images
    | internally. You may choose one of them according to your PHP
    | configuration. By default PHP's "GD Library" implementation is used.
    |
    | Supported: "gd", "imagick"
    |
    */

    'dynamic_generate' => true,

    'types' => [
        'profile' => [
            'original' => storage_path() . '/profile',
            'crop' => [
                'enabled' => true,
                'uncropped_image' => storage_path() . '/profile/uncropped',
            ],
            'compiled' => 'images/profile',
            'default' => 'images/profile-default.jpg',
            'sizes' => [
                'small' => [100, 100, 'fit'],
                'normal' => [250, 250, 'stretch']
            ]
        ],
    ],

);
