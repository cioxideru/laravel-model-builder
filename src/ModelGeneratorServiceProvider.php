<?php
namespace Jimbolino\Laravel\ModelBuilder;
/**
 * @author iBaranov <cioxideru@gmail.com>
 * Date: 13.07.15
 */

use Illuminate\Support\ServiceProvider;

class ModelGeneratorServiceProvider extends ServiceProvider {
	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->registerCommand();
	}

	protected function registerCommand()
	{
		$this->app['model-generator-command'] =
		$command = $this->app->share(function($app){
			return new ModelGeneratorCommand();
		});
		$this->commands('model-generator-command');
	}
	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array();
	}
}