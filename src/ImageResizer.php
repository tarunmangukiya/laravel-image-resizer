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

    /**
     * Overrides configuration settings
     *
     * @param array $config
     */
    public function configure(array $config = array())
    {
        $this->config = array_replace($this->config, $config);
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

    public function upload($type, $input, $name)
    {
        // Get Configurations
        $config = $this->config;
        $original = $config['types'][$type]['original'];
        $compiled = $config['types'][$type]['compiled'];
        $sizes = $config['types'][$type]['sizes'];

        // Save the original Image File
        $uploaded = \Request::file($input);
        $ext = $uploaded->getClientOriginalExtension();
        $slug = str_slug($name);
        $rand = str_random(7);
        $filename = "$slug-$rand.$ext";
        $file = $uploaded->move($original, $filename);

        foreach ($sizes as $key => $s) {
            // open an image file
            $img = \Image::make($file->getRealPath());

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
        }

        return $filename;
    }

    public function get($type, $size, $filename)
    {
        // Get Configurations
        $config = $this->config;
        $original = $config['types'][$type]['original'];
        $compiled = $config['types'][$type]['compiled'];
        $s = $config['types'][$type]['sizes'][$size];

        $path = "$compiled/$size/$filename";

        $pathinfo = pathinfo($path);
        $new_path = $pathinfo["dirname"] . "/" . $pathinfo["filename"] . "-$s[0]x$s[1]." . $pathinfo["extension"];

        if(!file_exists($new_path) && isset($config['types'][$type]['default']))
            return $config['types'][$type]['default'];
        return $new_path;
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
