# Laravel Image Resizer

Laravel Image Resizer is a **Laravel 5.x Package** for Image uploading, auto re-sizing and retrieving library. The package allows to define types of images and their directories to upload image, apply transformations on them using **Intervention Image** such as Crop and Rotate, and save different types of images (such as small, medium, large) using sync or push the job on to Laravel Queue.

## Features

- Save File via File Input or from an URL of file
- Leave crop, resize and saving the file in different dimensions on Laravel Image Resizer
- Supports conversion of file types such as jpg, png or keep original extension
- Supports to give append random text to file name to avoid any conflicts
- Supports Image Resizing using background Laravel Job
- Easy Image Retrieval based on file name of image
- Supports Image Retrieval from CDN (Supports Pull CDN)
- For new users, you can generate image dynamically for your existing images on the go. When user requests the resized resource it will be generated and served. (This can be useful for those who are alreading having previous data and want to use Laravel Image Resizer, just define your config file and images will be generated on the time of image retrieval.)

## Getting Started

1. [Installation](#install)
2. [Configuration](#configuration)
3. [Quick Start](#quick-start)
4. [Options](#options)
5. [Demo](http://laravel-image-resizer.azurewebsites.net)

## Install

Begin by installing this package through Composer.

```js
	{
	    "require": {
	    	"tarunmangukiya/laravel-image-resizer": "dev-master"
		}
	}
```

## Configuration

Laravel Image Resizer requires a configuration file for images to be resized. To get started, you'll need to publish all vendor assets:

```bash
php artisan vendor:publish
```

You can add the ```--provider="TarunMangukiya\ImageResizer\ImageResizerServiceProvider"``` option to only publish assets of the Image Resizer package.

This will create a `config/imageresizer.php` file in your app that you can modify to set your configuration. Also, make sure you check for changes compared to the original config file after an upgrade.

### Sample Configuration File

We have defined a sample configuration file for your reference.

```php
return array(
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
                'large' => [400, null, 'stretch']
            ]
        ],
    ],
);
```

Here in this config file we have defined one image type `profile` and the location of `original` image storage, `compiled` (resized) images location, `sizes` of the images that has to be resized like `small`, `large`, etc.

We have also defined `default` image to be returned in case of original image does not exists.

Thus, by using this basic configuration your **profile** image will be saved in original, small and large format with their defined respective sizes.

## Quick Start

You can upload & resize your images both from **File Input** or from **URL**.

If you want to process your image from File Input, you just need to write

```php
$file = \ImageResizer::upload('profile', 'file_input_name', $output_file_name);
```

or pass an url of the image file

```php
$url = 'https://invinciblengo.org/photos/slider/large/dalhousie-winter-trekking-expedition-himachal-pradesh-2RV7Udy-1337x390.jpg';
$file = \ImageResizer::upload('profile', $url, $output_file_name);
```

At the time of retrieval of image,
```html
<img src="{{ ImageResizer::get('profile', 'small', $filename) }}">
```

## Options

All the options available for config are defined in config file (imageresizer.php) of the package.

## Contact
You can write me at [@tarunmangukiya](https://twitter.com/TarunMangukiya) for additional information.


**Don't forget to provide your suggestions and reviews for continuous improvements.**