<?php
/**
 * @author iBaranov <cioxideru@gmail.com>
 * Date: 10.07.15
 */
namespace Jimbolino\Laravel\ModelBuilder;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class ModelGeneratorCommand extends Command
{

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'tizbi:generate:models';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Generate models';


	protected $generator;

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$baseModel = $this->option('base_model');
		$path = app_path($this->option('app_path'));

		$namespace = $this->option('namespace');
		$force = $this->option('force');

		$prefix = Database::getTablePrefix();

		$this->generator = new ModelGenerator($baseModel, $path, $namespace, $prefix,$force);
		$this->info('Model Generation Start');
		$this->generator->start();
		$this->info('Generation is Done');
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
		);
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
			array('base_model', null, InputOption::VALUE_OPTIONAL, 'base_model = \Illuminate\Database\Eloquent\Model', '\Illuminate\Database\Eloquent\Model'),
			array('app_path', null, InputOption::VALUE_OPTIONAL, 'app_path=models','Models'),
			array('namespace', null, InputOption::VALUE_OPTIONAL, 'namespace=App','App\\Models'),
			array('force', null, InputOption::VALUE_OPTIONAL, 'rewrite child models force=false',false),
		);
	}


}
