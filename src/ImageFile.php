<?php
namespace TarunMangukiya\ImageResizer;

use Closure;
use Exception;

class ImageFile
{
    /**
     * Mime type
     *
     * @var string
     */
    public $mime;
    
    /**
     * Original Name given by user of current file
     *
     * @var string
     */
    public $originalname;

    /**
     * Filename of current file (without extension)
     *
     * @var string
     */
    public $filename;

    /**
     * File extension of current file
     *
     * @var string
     */
    public $extension;
    
    /**
     * File extension of current file
     *
     * @var string
     */
    public $size = [];

    /**
     * File name of current file
     *
     * @var string
     */
    
    public function getBaseName()
    {
        return "{$this->filename}.{$this->extension}";
    }

    /**
     * Full Location of current file
     *
     * @var string
     */
    public $fullpath;


    /**
     * Sets all instance properties from given path
     *
     * @param string $path
     */
    public function setFileInfoFromPath($path)
    {
        $info = pathinfo($path);
        $this->fullpath = $path;
        $this->dirname = array_key_exists('dirname', $info) ? $info['dirname'] : null;
        $this->originalname = array_key_exists('basename', $info) ? $info['basename'] : null;
        $this->filename = array_key_exists('filename', $info) ? $info['filename'] : null;
        $this->extension = array_key_exists('extension', $info) ? $info['extension'] : null;

        if (file_exists($path) && is_file($path)) {
            $this->size = getimagesize($path);
            $this->mime = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $path);
        }

        return $this;
    }

     /**
      * Get file size
      * 
      * @return mixed
      */
    public function filesize()
    {
        $path = $this->basePath();

        if (file_exists($path) && is_file($path)) {
            return filesize($path);
        }
        
        return false;
    }

     /**
      * Get image file size
      * 
      * @return mixed
      */
    public function sizes()
    {
        if(empty($this->size)){
            $path = $this->fullpath;

            if (file_exists($path) && is_file($path)) {

                $this->size = getimagesize($path);

                // We need to use exif data as getimagesize provides invalid width, height if image is rotated
                $exif = exif_read_data($path);

                if(!empty($exif['Orientation'])) {
                    if($exif['Orientation'] === 8 || $exif['Orientation'] === 6) {
                        // 8 = CW Rotate Image to get original
                        // 6 = CCW Rotate Image to get original

                        // Store width as height & height as width
                        $height = $this->size[0];
                        $width = $this->size[1];

                        $this->size[0] = $width;
                        $this->size[1] = $height;
                        $this->size[3] = 'width="'.$width.'" height="'.$height.'"';
                    }
                }
            
                return $this->size;
            }
        }
        else {
            return $this->size;
        }
        return false;
    }

    /**
      * Get file path by string
      * 
      * @return string
      */
    public function __toString ()
    {
        return $this->getBaseName();
    }

}