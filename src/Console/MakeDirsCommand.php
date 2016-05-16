<?php namespace TarunMangukiya\ImageResizer\Console;

use Illuminate\Config\Repository;
use Illuminate\Console\Command;
use TarunMangukiya\ImageResizer\ImageResizer;

class MakeDirsCommand extends Command
{
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'imageresizer:makedirs';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Make directories according to the imageresizer config';

	/**
	 * @var ImageResizer Instance
	 */
	protected $imageResizer;

	/**
	 * Create a new console command instance.
	 *
	 * @param \Illuminate\Config\Repository $config
	 */
	public function __construct(Repository $config)
	{
		parent::__construct();

		$this->imageResizer = new ImageResizer($config);
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$result = $this->imageResizer->makeDirs();

		if (!$result) {
			$this->error('Make directories failed, please check properly that you have permission to create folders!');

			return;
		}

		$this->info('Image Resizer Directories created successfully.');
	}
}