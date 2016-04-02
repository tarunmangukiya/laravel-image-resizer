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
      * Get file path by string
      * 
      * @return string
      */
    public function __toString ()
    {
        return $this->getFileName();
    }

}