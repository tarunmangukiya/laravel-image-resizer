<?php

namespace TarunMangukiya\ImageResizer\Facades;

use Illuminate\Support\Facades\Facade;

class ImageResizer extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'ImageResizer';
    }
}
