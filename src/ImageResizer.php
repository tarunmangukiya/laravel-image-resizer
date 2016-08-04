<?php

namespace TarunMangukiya\ImageResizer;

use Closure;
use Exception;
use Intervention\Image\ImageManager as InterImage;
use TarunMangukiya\ImageResizer\ImageResizerConfig;
use TarunMangukiya\ImageResizer\Commands\ResizeImages;

class ImageResizer
{
    /**
     * Config
     *
     * @var array
     */
    public $config = array();

    /**
     * Intervention ImageManager Instance
     *
     * @var instance of InterImage
     */
    private $interImage;

    /**
     * GuzzleHttp Client Instance for Transferring file from url
     * 
     * @var instance of \GuzzleHttp\Client
     */

    private $guzzleHttp;

    /**
     * Creates new instance of Image Resizer
     *
     * @param array $config
     */
    public function __construct(array $config = array())
    {
        $this->config = ImageResizerConfig::configure($config);

        // Pass Intervention Image config
        $interConfig = config('image');
        $this->interImage = new InterImage($interConfig);
        $this->guzzleHttp = new \GuzzleHttp\Client([
                        'headers' => [
                            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36',
                            'Accept'     => 'image/png,image/gif,image/jpeg,image/pjpeg;q=0.9,text/html,application/xhtml+xml,application/xml;q=0.8,*.*;q=0.5'
                        ]
                    ]);
    }

    /**
     * Information about package
     * @return string
     */

    public function info()
    {
        echo "Tarun Mangukiya ImageResizer Package for Laravel 5.1+";
    }

    /**
     * Check If the provided url or extension is valid
     * @param type $url 
     * @return type
     */
    public function hasImageExtension($url) 
    {
        $valid_image_extensions = $this->config['valid_extensions'];
        $extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
        if(in_array($extension, $valid_image_extensions)) {
            return $extension;
        }
        return false;
    }

    /**
     * Move Uploaded file to the specified location
     * @param string $input 
     * @param string $name 
     * @param string $location 
     * @return ImageFile instance
     */
    protected function moveUploadedFile($input, $name, $location)
    {
        $uploaded = \Request::file($input);

        // Save the original Image File
        $image_file = new ImageFile;
        $image_file->originalname = $uploaded->getClientOriginalName();
        $image_file->filename = $this->generateFilename($name);
        $image_file->extension = $uploaded->getClientOriginalExtension();
        $image_file->mime = $uploaded->getMimeType();
        $image_file->fullpath = $location . '/' . $image_file->getBaseName();
        
        $uploaded->move($location, $image_file->getBaseName());

        return $image_file;
    }

    /**
     * Copy file to the specified location
     * @param string $input 
     * @param string $name 
     * @param string $location 
     * @return ImageFile instance
     */
    protected function copyFile($input, $name, $location)
    {
        //dd($input);
        if(($extension = $this->hasImageExtension($input)) === false && !exif_imagetype($input)) {
            if($this->config['clear_invalid_uploads']){
                \File::delete($input);
            }

            throw new \TarunMangukiya\ImageResizer\Exception\InvalidInputException("Invalid Input for Image Resizer.");
        }

        
        // Default Extension will be jpg
        if(!$extension) $extension = 'jpg';
        $filename = $this->generateFilename($name).'.'.$extension;
        $fullpath = $location .'/'. $filename;

        \File::copy($input, $fullpath);
        
        $image_file = new ImageFile;
        $image_file = $image_file->setFileInfoFromPath($fullpath);
        
        return $image_file;
    }

    /**
     * Generate File Name for images
     * @param string $name 
     * @return string
     */
    public function generateFilename($name)
    {
        $slug = str_slug($name);
        if($this->config['append_random_characters']) {
            $slug .= '-' . str_random(7);
        }
        return $slug;
    }

    /**
     * Transfer file from http/https url to support online url upload
     * @param string $input 
     * @param string $name 
     * @param string $location 
     * @return ImageFile instance
     */
    public function transferURLFile($input, $name, $location)
    {
        // Save the original Image File from URL

        // Identify the extension of the file, as many files would be accessible via 
        // token or access codes only
        // thus we need to seperate a proper extension from it
        $extension = $this->hasImageExtension($input);
        // Default Extension will be jpg
        if(!$extension) $extension = 'jpg';

        $filename = $this->generateFilename($name).'.'.$extension;

        $fullpath = $location .'/'. $filename;

        // Get page using HTTP GET Request as loading copying directly causes 403 error sometimes in different cases of redirect
        try {
            $response = $this->guzzleHttp->get($input, [
                    'sink' => $fullpath
                ]);
        } catch (\Exception $e) {
            throw new \TarunMangukiya\ImageResizer\Exception\InvalidInputException("Invalid Input for Image Resizer.");
        }

        $image_file = new ImageFile;
        $image_file = $image_file->setFileInfoFromPath($fullpath);
        
        if(!exif_imagetype($fullpath)) {
            if($this->config['clear_invalid_uploads']){
                \File::delete($fullpath);
            }

            throw new \TarunMangukiya\ImageResizer\Exception\InvalidInputException("Invalid Input for Image Resizer.");
        }
        return $image_file;
    }

    /**
     * Apply Crop, Rotate Transofrmations Image File if enabled in config
     * @param string $original_file 
     * @param array $type_config 
     * @param array $crop 
     * @param array|null $rotate 
     * @return ImageFile instance
     */
    public function transformImage($original_file, $type_config, $crop, $rotate = null)
    {
        $img = $this->interImage->make($original_file->fullpath);

        // Reset Image Rotation before doing any activity
        $img->orientate();
        
        // If crop dimenssion is relative
        if(count($crop) == 4)
        {
            // ($width, $height, $x, $y)
            $img->crop($crop[0], $crop[1], $crop[2], $crop[3]);
        }
        // If crop dimenssion is absolute
        else if(count($crop) == 2)
        {
            // ($width, $height)
            $img->crop($crop[0], $crop[1]);
        }
        else
        {
            throw new \TarunMangukiya\ImageResizer\Exception\InvalidCropDimensionException('Invalid Crop Dimensions provided.');
        }

        // Rotate Image 
        if($rotate !== null){
            if(count($rotate) == 2) {
                $img->rotate($rotate[0], $rotate[1]);
            }
            else{
                $img->rotate($rotate[0]);
            }
        }

        // Generate Real Path for the resizing input
        $location = $type_config['original'] .'/'. $original_file->getBaseName();
        // finally we save the image as a new file
        $img->save( $location );
        $img->destroy();

        $file = new ImageFile;
        return $file->setFileInfoFromPath($location);
    }

    /**
     * Upload & Resize the file from \File or url
     * @param string $type 
     * @param string $input 
     * @param string $name 
     * @param array|null $crop 
     * @param array|null $rotate 
     * @return ImageFile instance
     */
    public function upload($type, $input, $name, $crop = null, $rotate = null, $override_config = [])
    {
        if(strlen($name) > 255) 
            throw new \TarunMangukiya\ImageResizer\Exception\TooLongFileNameException("Error Processing Request", 1);
            
        // Get Config for the current Image type
        $type_config = ImageResizerConfig::getTypeConfig($type, $override_config);
        $crop_enabled = $type_config['crop']['enabled'];

        // Get the original save location according to config
        if($crop_enabled && null !== $crop) {
            $original_location = array_key_exists('uncropped_image', $type_config['crop']) ? $type_config['crop']['uncropped_image'] : $type_config['original'];
        }
        else {
            $original_location = $type_config['original'];
        }

        // Check input type is $_FILES input name or local file or url

        // Check for Input File
        if(\Request::hasFile($input)) {
            $original_file = $this->moveUploadedFile($input, $name, $original_location);
        }
        // Check if file exists locally
        elseif (file_exists($input)) {
            $original_file = $this->copyFile($input, $name, $original_location);
        }
        // Check if input is url then fetch image from online
        elseif (filter_var($input, FILTER_VALIDATE_URL) !== FALSE) {
            $original_file = $this->transferURLFile($input, $name, $original_location);
        }
        else {
            throw new \TarunMangukiya\ImageResizer\Exception\InvalidInputException("Invalid Input for Image Resizer.");
        }
        
        // crop the image if enabled
        if($crop_enabled && null !== $crop) {
            $file = $this->transformImage($original_file, $type_config, $crop, $rotate);
        }
        else{
            $file = $original_file;
        }

        // Check if there is no resize of files
        if(count($type_config['sizes'])) {
            $job = new ResizeImages($file, $type_config);
            // Check if we have to queue the resize of the image        
            if($this->config['queue']){
                \Queue::pushOn($this->config['queue_name'], $job);
            }
            else {
                $job->handle();
            }
        }

        return $file;
    }

    /**
     * Retrive Resized Image from File Basename
     * @param string $type 
     * @param string $size 
     * @param string $basename 
     * @return string
     */
    public function get($type, $size, $basename)
    {
        $public_file = $this->getPublicPath($type, $size, $basename);
        return url($public_file);
    }

    /**
     * Retrive Resized Image from File Basename in base64 format
     * @param string $type 
     * @param string $size 
     * @param string $basename 
     * @return string
     */
    public function getBase64($type, $size, $basename)
    {
        return $this->getPublicPath($type, $size, $basename, true);
    }

    /**
     * Retrive Resized Image Path/base64 from File Basename
     * @param string $type 
     * @param string $size 
     * @param string $basename 
     * @param boolean $base64
     * @return string
     */
    public function getPublicPath($type, $size, $basename, $base64 = false)
    {   
        $config = $this->config;
        $type_config = ImageResizerConfig::getTypeConfig($type);

        $files = $this->getOutputPaths($type, $size, $basename);
        extract($files);

        // If Return type is base64 
        if($base64) {
            //$type = pathinfo($compiled_file, PATHINFO_EXTENSION);
            
            $type = (string) $this->interImage->make($compiled_file)->mime();
            // encode('data-url');
            $data = file_get_contents($compiled_file);
            $base64 = 'data:' . $type . ';base64,' . base64_encode($data);
            return $base64;
        }

        if(file_exists($compiled_file)){
            return $public_file;
        }
        else if($config['dynamic_generate'] && $size != 'original' && file_exists($original_file)){
            $url = "resource-generate-image?filename=".urlencode($basename)."&type=".urlencode($type)."&size=".urlencode($size);
            return $url;
        }
        else if(isset($type_config['default'])){
            return $config['types'][$type]['default'];
        }
        return $public_file;
    }

    public function getRealPath($type, $size, $basename)
    {
        $files = $this->getOutputPaths($type, $size, $basename);
        extract($files);
        return $compiled_file;
    }

    public function getOutputPaths($type, $size, $basename)
    {
        $this->changeDir();

        $config = $this->config;
        $type_config = ImageResizerConfig::getTypeConfig($type);

        if(empty($basename)){
            if(isset($type_config['default'])) {
                return \URL::to($type_config['default']);
            } else {
                return '';
            }
        }

        $original = $type_config['original'];
        $compiled_path = $type_config['compiled'];

        $original_file = "$original/$basename";

        // Check if user wants the original Image
        if($size == 'original')
        {
            $compiled_file = $public_file = $original_file;
        }
        else
        {
            // Get Configurations for specific size
            $s = $type_config['sizes'][$size];
            $width = $s[0];
            $height = $s[1];

            // File Name match
            $pathinfo = pathinfo($basename);
            $filename = isset($pathinfo['filename'])?$pathinfo['filename']:'';
            $file_extension = isset($pathinfo['extension'])?$pathinfo['extension']:'';

            // Match Proper Extension
            $extension = $s[3];
            if($extension == 'original') $extension = $file_extension;

            $compiled_file = "{$compiled_path}/{$size}/{$filename}-{$width}x{$height}.{$extension}";
            
            if(!empty($type_config['public'])) {
                $public = $type_config['public'];
                $public_file = "{$public}/{$size}/{$filename}-{$width}x{$height}.{$extension}";
            }
            else {
                $public_file = $compiled_file;
            }
        }

        return compact('compiled_file', 'public_file');
    }

    /**
     * Helper Function to create directories according to config
     * @return void
     */
    public function makeDirs()
    {
        $this->changeDir();
        
        $types = $this->config['types'];
        foreach ($types as $key => $type) {
            $sizes = $type['sizes'];
            
            //Create Directory for Original Folders
            $path = $type['original'];
            \File::makeDirectory($path, 0777, true, true);

            //Create Directory for All Target Folders
            foreach ($sizes as $key => $size) {
                $path = $type['compiled'].'/'.$key;
                \File::makeDirectory($path, 0777, true, true);
            }
        }
        return true;
    }

    /**
     * Before handling the file resize
     * change the directory to public path of laravel
     * as many of the path will be used from public_path
     * @return void
     */
    public function changeDir()
    {
        chdir(public_path());
    }
}
