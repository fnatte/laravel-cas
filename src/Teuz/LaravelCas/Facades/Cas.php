<?php namespace Teuz\LaravelCas\Facades;

class Cas extends \Illuminate\Support\Facades\Facade
{

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'cas'; }

}