<?php

namespace TarunMangukiya\ImageResizer;

use Closure;
use Exception;
use Intervention\Image\ImageManager as InterImage;

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
     * @var array
     */
    public $interImage = array();

    /**
     * Creates new instance of Image Resizer
     *
     * @param array $config
     */
    public function __construct(array $config = array())
    {
        $this->configure($config);
        $this->interImage = new InterImage;
    }

    public function getDefaultTypeConfig()
    {
        $type = array(
            'original' => public_path() . '/',
            'crop' => [
                'enabled' => false
            ],
            'compiled' => '',
            'sizes' => []
        );
        return $type;
    }

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

    public function info()
    {
        echo "Tarun Mangukiya ImageResizer Package for Laravel 5.0+";
    }

    public function getTypeConfig($type)
    {
        if(!isset($this->config['types'][$type]))
            throw new \TarunMangukiya\ImageResizer\Exception\InvalidTypeException("Invalid Image Resize Type '{$type}'. Please check your config.");
            
        return $this->config['types'][$type];
    }

    public function moveUploadedFile($input, $name, $location)
    {
        $uploaded = \Request::file($input);

        // Save the original Image File
        $image_file = new ImageFile;
        $image_file->originalname = $uploaded->getClientOriginalName();
        $image_file->extension = $uploaded->getClientOriginalExtension();
        $image_file->basename = $this->generateFilename($name);
        $image_file->mime = $uploaded->getMimeType();
        $image_file->fullpath = $location . '/' . $image_file->getFileName();
        
        $uploaded->move($location, $image_file->getFileName());

        return $image_file;
    }

    public function generateFilename($name)
    {
        $slug = str_slug($name);
        $rand = str_random(7);
        return "$slug-$rand";
    }

    public function transferURLFile($input, $name, $location)
    {
        // Save the original Image File from URL
        $filename = $this->generateFilename($name).'.'.pathinfo($input, PATHINFO_EXTENSION);

        $fullpath = $location .'/'. $filename;
        \File::copy($input, $fullpath);

        $image_file = new ImageFile;
        $image_file = $image_file->setFileInfoFromPath($fullpath);
        return $image_file;
    }

    public function cropImage($original_file, $type_config, $dimensions)
    {
        $img = $this->interImage->make($original_file->fullpath);

        // If crop dimenssion is relative
        if(count($dimensions) == 4)
        {
            // ($width, $height, $x, $y)
            $img->crop($dimensions[0], $dimensions[1], $dimensions[2], $dimensions[3]);
        }
        // If crop dimenssion is absolute
        else if(count($dimensions) == 2)
        {
            // ($width, $height)
            $img->crop($dimensions[0], $dimensions[1]);
        }
        else
        {
            throw new \TarunMangukiya\ImageResizer\Exception\InvalidCropDimensionException('Invalid Crop Dimensions provided.');
        }

        // Generate Real Path for the resizing input
        $location = $type_config['original'] .'/'. $original_file->getFileName();
        // finally we save the image as a new file
        $img->save( $location );
        $img->destroy();

        $file = new ImageFile;
        return $file->setFileInfoFromPath($location);
    }

    public function resizeImage($fullpath, $target, $size)
    {
        $img = $this->interImage->make($fullpath);

        // Check if height or width is set to auto then resize must be according to the aspect ratio
        if($size[0] == null || $size[1] == null){
            $img->resize($size[0], $size[1], function ($constraint) {
                $constraint->aspectRatio();
            });
        }
        elseif ($size[2] == 'stretch') {
            // Stretch Image
            $img->resize($size[0], $size[1]);
        }
        else {
            // Default Fit
            $img->fit($size[0], $size[1]);
        }

        // finally we save the image as a new file
        $img->save($target);
        $img->destroy();
    }

    public function resizeAnimatedImage($fullpath, $target, $size)
    {
        // Extract image using \GifFrameExtractor\GifFrameExtractor;

        //$gifExtractor = new \Intervention\Gif\Decoder($fullpath);
        //$decoded = $gifExtractor->decode();

        $gifFrameExtractor = new \GifFrameExtractor\GifFrameExtractor;
        $frames = $gifFrameExtractor->extract($fullpath);   

        // Check if height or width is set to auto then resize must be according to the aspect ratio
        if($size[0] == null || $size[1] == null){
            // Resize each frames
            foreach ($frames as $frame) {
                $img = $this->interImage->make($frame['image']);
                $img->resize($size[0], $size[1], function ($constraint) {
                    $constraint->aspectRatio();
                });
                // $img->save(str_replace('.', uniqid().'.', $target));
                $framesProcessed[] = $img->getCore();
            }
        }
        elseif ($size[2] == 'stretch') {
            // Stretch Image
            // Resize each frames
            foreach ($frames as $frame) {
                $img = $this->interImage->make($frame['image']);
                $img->resize($size[0], $size[1]);
                $framesProcessed[] = $img->getCore();
            }
        }
        else {
            // Default Fit
            // Resize each frames
            foreach ($frames as $frame) {
                $img = $this->interImage->make($frame['image']);
                $img->fit($size[0], $size[1]);
                $framesProcessed[] = $img->getCore();
            }
        }

        $gifCreator = new \GifCreator\GifCreator;
        $gifCreator->create($framesProcessed, $gifFrameExtractor->getFrameDurations(), 0);
        $gifBinary = $gifCreator->getGif();
        $gifCreator->reset();

        \File::put($target, $gifBinary);

        // Release Memory
        unset($gifFrameExtractor);
        unset($gifCreator);
    }

    public function resizeTypeImages($file, $type_config)
    {
        $sizes = $type_config['sizes'];
        $compiled_path = $type_config['compiled'];
        $filename = $file->basename;

        foreach ($sizes as $folder => $size) {
            $width = $size[0];
            $height = $size[1];
            $scaling = $size[2];
            $extension = $size[3];

            if($extension == 'original') $extension = $file->extension;

            $is_animated = false;
            if($extension == 'gif' && $size[4] == 'animated') {
                // Check if animated is enabled for gif images
                if($type_config['crop']['enabled']) {
                    throw new \TarunMangukiya\ImageResizer\Exception\InvalidConfigException('Crop function along with animated gif is not allowed. Please disable crop or animated gif resize in config.');
                }
                if(!class_exists('\GifFrameExtractor\GifFrameExtractor') || !class_exists('\GifCreator\GifCreator')){
                    throw new \TarunMangukiya\ImageResizer\Exception\InvalidConfigException('You need to install "Sybio/GifFrameExtractor" and "Sybio/GifCreator" packages to resize animated gif files.');
                }
                $is_animated = \GifFrameExtractor\GifFrameExtractor::isAnimatedGif($file->fullpath);
            }

            $target = "{$compiled_path}/{$folder}/{$filename}-{$width}x{$height}.{$extension}";
            
            if($is_animated){
                $this->resizeAnimatedImage($file->fullpath, $target, $size);
            }
            else{
                // resize normal non-animated files
                $this->resizeImage($file->fullpath, $target, $size);
            }
        }
    }

    public function upload($type, $input, $name, $crop = null, $rotate = null)
    {
        // Get Config for the current Image type
        $type_config = $this->getTypeConfig($type);
        $crop_enabled = $type_config['crop']['enabled'];

        // Get the original save location according to config
        if($crop_enabled) {
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
        if($crop_enabled) {
            $file = $this->cropImage($original_file, $type_config, $crop);
        }
        else{
            $file = $original_file;
        }

        $this->resizeTypeImages($file, $type_config);

        return $file;
    }

    public function upload_old($type, $input, $name, $crop_dimentions = null)
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

    public function get($type, $size, $basename)
    {
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
            $new_path = "$compiled_path/$basename";
        }
        else
        {
            // Get Configurations for specific size
            $s = $type_config['sizes'][$size];
            $width = $s[0];
            $height = $s[1];

            // File Name match
            $pathinfo = pathinfo($basename);
            $filename = $pathinfo['filename'];
            $file_extension = $pathinfo['extension'];

            // Match Proper Extension
            $extension = $s[3];
            if($extension == 'original') $extension = $file_extension;
            $new_path = "{$compiled_path}/{$size}/{$filename}-{$width}x{$height}.{$extension}";
        }

        if(file_exists($new_path)){
            return \URL::to($new_path);
        }
        else if(!file_exists("$original/$filename") && isset($type_config['default'])){
            return \URL::to($config['types'][$type]['default']);
        }
        else if($config['dynamic_generate'] && $size != 'original'){
            $url = "resource-generate-image?filename=".urlencode($basename)."&type=".urlencode($type)."&size=".urlencode($size);
            return \URL::to($url);
        }
        return \URL::to($new_path);
    }

    public function makeDirs()
    {
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
    }
}
