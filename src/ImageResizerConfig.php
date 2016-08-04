<?php

namespace TarunMangukiya\ImageResizer;

class ImageResizerConfig
{
    /**
     * Config
     *
     * @var array
     */
	public static $config = [];

    /**
     * Default Config for Image Resizer
     * @return array
     */
    public static function getDefaultTypeConfig()
    {
        $type = array(
            'original' => public_path(),
            'crop' => [
                'enabled' => false
            ],
            'compiled' => '',
            'sizes' => [],
            'watermark' => [
                'enabled' => false
            ]
        );
        return $type;
    }

    /**
     * Default config format for size array of image type
     * @return array
     */
    public static function getDefaultSizeConfig()
    {
        $size = [ null, null, 'fit', 'jpg', 'non-animated' ];
        return $size;
    }

    /**
     * Overrides configuration settings
     *
     * @param array $config
     */
    public static function configure(array $config = array())
    {
    	$new_config = array_replace(self::$config, $config);
        $default_type = self::getDefaultTypeConfig();

        // Types of images must be combined with default values
        $size = self::getDefaultSizeConfig();
        foreach ($new_config['types'] as $key => $value) {
            $new_config['types'][$key] = array_replace($default_type, $new_config['types'][$key]);
            foreach ($new_config['types'][$key]['sizes'] as &$s) {
                $s = array_replace($size, $s);
            }
        }
        
        self::$config = $new_config;
        
        return $new_config;
    }

	/**
     * Get Config
     * @return array
     */
    public static function getConfig()
    {
        return self::$config;
    }

    /**
     * Get Config related to specific type only
     * @param string $type 
     * @param array $override_config
     * @return array
     */
    public static function getTypeConfig($type, $override_config = [])
    {
        if(!isset(self::$config['types'][$type]))
            throw new \TarunMangukiya\ImageResizer\Exception\InvalidTypeException("Invalid Image Resize Type '{$type}'. Please check your config.");
        
        $config = self::$config['types'][$type];
        $config = array_replace($config, $override_config);

        return $config;
    }

    /**
     * Get Config related to specific type and size
     * @param string $type 
     * @param string $size 
     * @return array
     */
    public static function getTypeSizeConfig($type, $size)
    {
        if(!isset(self::$config['types'][$type]))
            throw new \TarunMangukiya\ImageResizer\Exception\InvalidTypeException("Invalid Image Resize Type '{$type}'. Please check your config.");

        $type_config = self::$config['types'][$type];            

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
}