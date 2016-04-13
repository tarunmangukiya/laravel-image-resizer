<?php namespace TarunMangukiya\ImageResizer\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Config\Repository;
use TarunMangukiya\ImageResizer\ImageResizer;

class MakeDirs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'imageresizer:makedirs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make directories according to the imageresizer config';

    /**
	 * Create a new console command instance.
	 *
	 * @param \Illuminate\Config\Repository $config
	 */
	public function __construct(array $config)
	{
		parent::__construct();

		$this->imageResizer = new ImageResizer($config);
	}
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $result = $this->imageResizer->makeDirs();

        if (!$result) {
            $this->error('Make directories failed, please check properly that you have permission to create folders!');

            return;
        }

        $this->info('Image Resizer Directories created successfully.');
    }
}