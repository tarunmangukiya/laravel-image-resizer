<?php namespace TarunMangukiya\ImageResizer;

use App\Http\Controllers\Controller;

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
    if(\Input::has('filename') && \Input::has('type') && \Input::has('size')){
      $filename = \Input::get('filename');
      $type = \Input::get('type');
      $size = \Input::get('size');

      // Get Configurations
      $config = \Config::get('imageresizer');
      $original = $config['types'][$type]['original'];
      $compiled = $config['types'][$type]['compiled'];
      $s = $config['types'][$type]['sizes'][$size];

      $input_file = "$original/$filename";

      if(!file_exists($input_file)){
        abort(404);
      }

      $pathinfo = pathinfo($filename);
      $output_file = $compiled . '/' . $size . '/' . $pathinfo["filename"] . "-$s[0]x$s[1]." . $pathinfo["extension"];

      // open an image file
      $img = \Image::make($input_file);

      switch ($s[2]) {
          case 'stretch':
              $img->resize($s[0], $s[1]);
              break;
          default:
              //Default Fit
              $img->fit($s[0], $s[1]);
              break;
      }

      // finally we save the image as a new file
      $img->save($output_file);
      $img->destroy();

      return \Redirect::to($output_file);
    }
    else{
      abort(404);
    }
  }
}