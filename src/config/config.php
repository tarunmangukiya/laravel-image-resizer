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
    | Base URL
    |--------------------------------------------------------------------------
    |
    | Provides the support of base url, this url will be used to serve the images.
    | You can use pull-cdn with the Laravel Image Resizer as a base url for production env.
    |
    | Default: ''
    |
    */

    'base_url' => '',

    /*
    |--------------------------------------------------------------------------
    | Ignore Environments
    |--------------------------------------------------------------------------
    |
    | The environments for which base_url will be ignored.
    | Better for local environment testing.
    |
    | Default: 'local'
    |
    */

    'ignore_environments' => array(
        'local',
    ),

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
    | 'compiled' => path where the compiled (resized) images will be saved, by deafult this path will be used to retrive the files
    | 'public' => used when we are storing all the images in Storage folder or in cloud, this path will be used to retrive the images instead of compiled path
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
            ],
            'watermark' => [
                'enabled' => false,
                'normal' => ['watermarks/watermark.png', 'bottom-right', 10, 10]
            ]
        ],
    ],


    'valid_extensions' => ['gif', 'jpeg', 'png', 'bmp', 'jpg'],

    /*
    |--------------------------------------------------------------------------
    | Append Random Characters at the end of filename
    |--------------------------------------------------------------------------
    |
    | Whether to append a random 7 characters at the end of file name generated
    | Useful to have unique file names for all the files saved
    |
    | Default: 'true'
    |
    */

    'append_random_characters' => true,

    /*
    |--------------------------------------------------------------------------
    | Clear Invalid Uploaded Files
    |--------------------------------------------------------------------------
    |
    | In case of Upload Image from URL, it may happen that someone tries to upload invalid image,
    | setting this true will delete the file to free the space.
    |
    | Default: 'true'
    |
    */

    'clear_invalid_uploads' => true,

);
