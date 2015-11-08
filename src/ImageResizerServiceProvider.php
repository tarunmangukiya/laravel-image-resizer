<?php

namespace TarunMangukiya\ImageResizer;

use Illuminate\Support\ServiceProvider;

class ImageResizerServiceProvider extends ServiceProvider
{
    

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes(array(
            __DIR__.'/config/config.php' => config_path('imageresizer.php')
        ));
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // merge default config
        //
        $this->mergeConfigFrom(
            __DIR__.'/config/config.php',
            'imageresizer'
        );

        /*
         * Register the service provider for the dependency.
         */
        $this->app->register('Intervention\Image\ImageServiceProvider');

        /*
         * Create aliases for the dependency.
         */
        $loader = \Illuminate\Foundation\AliasLoader::getInstance();
        $loader->alias('Image', 'Intervention\Image\Facades\Image');

        $this->app['ImageResizer'] = $this->app->share(function ($app) {
            return new ImageResizer($this->app['config']->get('imageresizer'));
        });

        $this->app->alias('ImageResizer', 'TarunMangukiya\ImageResizer\ImageManager');

        include __DIR__.'/routes.php';
    }
}
