<?php namespace Teuz\LaravelCas;

use Illuminate\Support\ServiceProvider;

class LaravelCasServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->package('teuz/laravel-cas');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->bindShared('cas', function($app)
		{
			$config = $app['config'];

			return new Cas(
				$app['auth'],
				$app['request'],
				$app['session'],
				$app['redirect'],
				$app['curl'],
				$config->get('laravel-cas::url'),
				$config->get('laravel-cas::service'),
				$config->get('laravel-cas::userField'),
				$config->get('laravel-cas::createUsers')
			);
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('cas');
	}

}