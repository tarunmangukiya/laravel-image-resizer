<?php namespace TarunMangukiya\ImageResizer;

use App\Http\Controllers\Controller;
use TarunMangukiya\ImageResizer\Commands\ResizeImages;

class ImageController extends Controller {

  public function __construct() {
    //$this->middleware('auth');
  }

  /**
  * Display a dynamically generated image.
  *
  * @return Response
  */
  public function getGenerateImage()
  {
    if(\Illuminate\Support\Facades\Input::has('filename') && \Illuminate\Support\Facades\Input::has('type') && \Illuminate\Support\Facades\Input::has('size')) {
      $basename = \Illuminate\Support\Facades\Input::get('filename');
      $type = \Illuminate\Support\Facades\Input::get('type');
      $size = \Illuminate\Support\Facades\Input::get('size');

      Facades\ImageResizer::info(); // forcing initialization, in particular loading the config
      $type_config = ImageResizerConfig::getTypeSizeConfig($type, $size);

      $image_file = new ImageFile;
      $image_file = $image_file->setFileInfoFromPath($type_config['original'].'/'.$basename);

      // have to execute the job in sync as the user is asking for the image, may be job is pending or failed, etc.
      $job = new ResizeImages($image_file, $type_config);
      $job->handle();

      $location = \ImageResizer::get($type, $size, $basename);
      return \Redirect::to($location);
    }
    else{
      abort(404);
    }
  }
}
