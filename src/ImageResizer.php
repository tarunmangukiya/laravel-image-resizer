<?php

namespace TarunMangukiya\ImageResizer;

use Closure;

class ImageResizer
{
    /**
     * Config
     *
     * @var array
     */
    public $config = array();

    /**
     * Creates new instance of Image Manager
     *
     * @param array $config
     */
    public function __construct(array $config = array())
    {
        $this->configure($config);
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
        foreach ($config['types'] as $key => $value) {
            $this->config['types'][$key] = array_replace($default_type, $this->config['types'][$key]);
        }
        return $this;
    }

    public function dosomething()
    {
        echo "Do something";
    }

    public function test()
    {
        
        dd($this->config);
        
        $file = \Request::file($input);
        $url = str_slug($name);
        $dest = env($path);
        $filename = $url.'-'.str_random(7).'.'.$file->getClientOriginalExtension();
        $file->move($dest, $filename);
    }

    public function upload($type, $input, $name, $crop_dimentions = null)
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

            if(count($crop_dimentions) == 4)
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

    public function get($type, $size, $filename)
    {
        $config = $this->config;
        if(empty($filename)){
            if(isset($config['types'][$type]['default'])){
                return \URL::to($config['types'][$type]['default']);
            }else{
                return '';
            }
        }

        // Check if user wants the original Image

        if($size == 'original')
        {
            // Get the original Image if it's in public folder
            $original = $config['types'][$type]['original'];

            $compiled = str_replace(public_path().'/', '', $original);

            //$compiled = $config['types'][$type]['compiled'];
            //$s = $config['types'][$type]['sizes'][$size];

            $new_path = "$compiled/$filename";
        }
        else
        {
            // Get Configurations for specific size
            $original = $config['types'][$type]['original'];
            $compiled = $config['types'][$type]['compiled'];
            $s = $config['types'][$type]['sizes'][$size];

            $path = "$compiled/$size/$filename";

            $pathinfo = pathinfo($path);
            $new_path = $pathinfo["dirname"] . "/" . $pathinfo["filename"] . "-$s[0]x$s[1]." . $pathinfo["extension"];
        }


        if(file_exists($new_path)){
            return \URL::to($new_path);
        }
        else if(!file_exists("$original/$filename") && isset($config['types'][$type]['default'])){
            return \URL::to($config['types'][$type]['default']);
        }
        else if($config['dynamic_generate'] && $size != 'original'){
            $url = "resource-generate-image?filename=".urlencode($filename)."&type=".urlencode($type)."&size=".urlencode($size);
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
