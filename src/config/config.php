<?php

return array(

    /*
    |--------------------------------------------------------------------------
    | Queue
    |--------------------------------------------------------------------------
    |
    | Whether the image resizing process should be queued
    |
    | Default: false
    |
    */

    'queue' => false,

    /*
    |--------------------------------------------------------------------------
    | Queue Name
    |--------------------------------------------------------------------------
    |
    | If queue is set to true, queue_name will be used to push the jobs in queue
    |
    | Default: 'imageresizer'
    |
    */

    'queue_name' => 'imageresizer',
    

    /*
    |--------------------------------------------------------------------------
    | Dynamic Generate
    |--------------------------------------------------------------------------
    |
    | Provides the support to generate the images dynamically
    | when the request is called by user.
    | Generally used when we are using queue to resize images,
    | it provides the support to generate and show resized images on the go,
    | despite of the job is executed or not.
    | Also can be useful when we want all our images to be re-generated on the go.
    |
    | Default: 'false'
    |
    */

    'dynamic_generate' => false,


    /*
    |--------------------------------------------------------------------------
    | Types of sizes
    |--------------------------------------------------------------------------
    |
    | Defines the types of sizes in which image has to be resized and stored.
    |
    | Appendix:
    | 'profile' => type of the image (you can give any name)
    | 'crop' => 'enabled' => whether the image has to be cropped or not,
    |         'uncropped_image' => path (where the uncropped image has to be saved)
    |                              not required, (if we do not want to save original image)
    | 'compiled' => path where the compiled (resized) images will be saved
    | 'default' => default image file (that has to be retured in case of image not found)
    |                                 (can be useful for setting default profile picture)
    | 'sizes' => sizes of the images with key that has to be resized
    |          'small' (any name can be used)
    |          format: [width, height, ['fit|stretch', ['file type: original, jpg, png, gif', ['animated: if gif image has to be animated in resizing format'] ] ] ]
    |
    |
    | Don't use public_path() function in case of image has to be stored in public folder.
    | (see, 'compiled', 'default' type values)
    |
    |
    */

    'types' => [
        'profile' => [
            'original' => storage_path() . '/profile',
            'crop' => [
                'enabled' => false,
                'uncropped_image' => storage_path() . '/profile/uncropped',
            ],
            'compiled' => 'images/profile',
            'default' => 'images/profile-default.jpg',
            'sizes' => [
                'small' => [100, 100, 'fit', 'jpg'],
                'normal' => [300, null, 'fit', 'original', 'animated'],
                'large' => [400, null, 'stretch']
            ]
        ],
    ],

);
