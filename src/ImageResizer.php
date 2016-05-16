<?php

namespace TarunMangukiya\ImageResizer;

use Closure;
use Exception;
use Intervention\Image\ImageManager as InterImage;
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
        $this->configure($config);
        $this->interImage = new InterImage;
        $this->guzzleHttp = new \GuzzleHttp\Client([
                        'headers' => [
                            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36',
                            'Accept'     => 'text/html,application/xhtml+xml,application/xml;q=0.9,/;q=0.8'
                        ]
                    ]);
    }


    /**
     * Default Config for Image Resizer
     * @return array
     */
    public function getDefaultTypeConfig()
    {
        $type = array(
            'original' => public_path() . '/',
            'crop' => [
                'enabled' => false
            ],
            'compiled' => '',
            'sizes' => [],
            'clear_invalid_uploads' => false
        );
        return $type;
    }

    /**
     * Default config format for size array of image type
     * @return array
     */
    public function getDefaultSizeConfig()
    {
        $size = [ null, null, 'fit', 'jpg', 'non-animated' ];
        return $size;
    }

    /**
     * Overrides configuration settings
     *
     * @param array $config
     */
    public function configure(array $config = array())
    {
        $this->config = array_replace($this->config, $config);
        $default_type = $this->getDefaultTypeConfig();
        
        // Types of images must be combined with default values
        $size = $this->getDefaultSizeConfig();
        foreach ($config['types'] as $key => $value) {
            $this->config['types'][$key] = array_replace($default_type, $this->config['types'][$key]);
            foreach ($this->config['types'][$key]['sizes'] as &$s) {
                $s = array_replace($size, $s);
            }
        }
        return $this;
    }

    /**
     * Information about package
     * @return string
     */

    public function info()
    {
        echo "Tarun Mangukiya ImageResizer Package for Laravel 5.0+";
    }


    /**
     * Get Config related to specific type only
     * @param string $type 
     * @return array
     */
    public function getTypeConfig($type)
    {
        if(!isset($this->config['types'][$type]))
            throw new \TarunMangukiya\ImageResizer\Exception\InvalidTypeException("Invalid Image Resize Type '{$type}'. Please check your config.");
            
        return $this->config['types'][$type];
    }

    /**
     * Get Confir related to specific type and size
     * @param string $type 
     * @param string $size 
     * @return array
     */
    public function getTypeSizeConfig($type, $size)
    {
        if(!isset($this->config['types'][$type]))
            throw new \TarunMangukiya\ImageResizer\Exception\InvalidTypeException("Invalid Image Resize Type '{$type}'. Please check your config.");

        $type_config = $this->config['types'][$type];            

        foreach ($type_config['sizes'] as $key => $s)
        {
            if($key === $size) {
                $required_size = $s;
                break;
            }
        }

        $type_config['sizes'] = [];
        $type_config['sizes'][$size] = $required_size;

        return $type_config;
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
     * Generate File Name for images
     * @param string $name 
     * @return string
     */
    public function generateFilename($name)
    {
        $slug = str_slug($name);
        $rand = str_random(7);
        return "$slug-$rand";
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
    public function upload($type, $input, $name, $crop = null, $rotate = null)
    {
        if(strlen($name) > 255) 
            throw new \TarunMangukiya\ImageResizer\Exception\TooLongFileNameException("Error Processing Request", 1);
            
        // Get Config for the current Image type
        $type_config = $this->getTypeConfig($type);
        $crop_enabled = $type_config['crop']['enabled'];

        // Get the original save location according to config
        if($crop_enabled && null !== $crop) {
            $original_location = array_key_exists('uncropped_image', $type_config['crop']) ? $type_config['crop']['uncropped_image'] : $type_config['original'];
        }
        else {
            $original_location = $type_config['original'];
        }

        // Check input type is url or $_FILES input name
        if(\Request::hasFile($input)) {
            $original_file = $this->moveUploadedFile($input, $name, $original_location);
        }
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

        $job = new ResizeImages($file, $type_config);
        // Check if we have to queue the resize of the image        
        if($this->config['queue']){
            \Queue::pushOn($this->config['queue_name'], $job);
        }
        else {
            $job->handle();
        }

        return $file;
    }

    /**
     * Removed** Upload Old function, kept here for reference only
     * @param type $type 
     * @param type $input 
     * @param type $name 
     * @param type|null $crop_dimentions 
     * @return type
     */
    private function upload_old($type, $input, $name, $crop_dimentions = null)
    {
        // Get Configurations
        $config = $this->config;
        $original = $config['types'][$type]['original'];
        $compiled = $config['types'][$type]['compiled'];
        $sizes = $config['types'][$type]['sizes'];

        // Check if cropping has to be applied
        $crop = $config['types'][$type]['crop']['enabled'];

        // Save the original Image File
        $uploaded = \Request::file($input);
        $ext = $uploaded->getClientOriginalExtension();
        $slug = str_slug($name);
        $rand = str_random(7);
        $filename = "$slug-$rand.$ext";

        if($crop && !empty($crop_dimentions))
        {
            // If path for un-cropped image is defined then image will be saved there
            // else it will be saved to original folder of image
            $uncropped_image = array_key_exists('uncropped_image', $config['types'][$type]['crop']) ? $config['types'][$type]['crop']['uncropped_image'] : $original;
            $file = $uploaded->move($uncropped_image, $filename);
            // New Random will be generated
            $rand = str_random(7);
            // File Name will be changed as the cropped image name should be returned
            $filename = "$slug-$rand.$ext";

            // Crop Image & Save
            $img = \Image::make($file->getRealPath());

            if(count($crop_dimentions) == 5)
            {
                $width = $crop_dimentions[0];
                $height = $crop_dimentions[1];
                $img->crop($crop_dimentions[0], $crop_dimentions[1], $crop_dimentions[2], $crop_dimentions[3]);
            }
            else if(count($crop_dimentions) == 2)
            {
                $width = $crop_dimentions[0];
                $height = $crop_dimentions[1];
                $img->crop($crop_dimentions[0], $crop_dimentions[1]);
            }
            else
            {
                throw new Exception('Invalid Crop Dimention value provided.');
            }

            // Generate Real Path for the resizing input
            $real_path = "$original/$filename";
            // finally we save the image as a new file
            $img->save($real_path);
            $img->destroy();
        }
        else
        {
            $file = $uploaded->move($original, $filename);
            $real_path = $file->getRealPath();
        }

        foreach ($sizes as $key => $s) {
            // open an image file
            $img = \Image::make($real_path);

            switch ($s[2]) {
                case 'stretch':
                    $img->resize($s[0], $s[1]);
                    break;
                default:
                    //Default Fit
                    $img->fit($s[0], $s[1]);
                    break;
            }

            $target = "$compiled/$key/$slug-$rand-$s[0]x$s[1].$ext";
            // finally we save the image as a new file
            $img->save($target);
            $img->destroy();
        }

        return $filename;
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
        $this->changeDir();

        $type_config = $this->getTypeConfig($type);

        $config = $this->config;
        if(empty($basename)){
            if(isset($type_config['default'])) {
                return \URL::to($type_config['default']);
            } else {
                return '';
            }
        }

        $original = $type_config['original'];
        $compiled_path = $type_config['compiled'];

        // Check if user wants the original Image
        if($size == 'original')
        {
            $new_path = "$original/$basename";
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
            $new_path = "{$compiled_path}/{$size}/{$filename}-{$width}x{$height}.{$extension}";
        }

        if(file_exists($new_path)){
            return \URL::to($new_path);
        }
        else if(!file_exists("$original/$basename") && isset($type_config['default'])){
            return \URL::to($config['types'][$type]['default']);
        }
        else if($config['dynamic_generate'] && $size != 'original'){
            $url = "resource-generate-image?filename=".urlencode($basename)."&type=".urlencode($type)."&size=".urlencode($size);
            return \URL::to($url);
        }
        return \URL::to($new_path);
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
