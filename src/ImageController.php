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
    if(\Input::has('filename') && \Input::has('type') && \Input::has('size')) {
      $basename = \Input::get('filename');
      $type = \Input::get('type');
      $size = \Input::get('size');

      $type_config = \ImageResizer::getTypeSizeConfig($type, $size);

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