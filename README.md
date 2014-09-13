# CAS for Laravel 4

*teuz/laravel-cas* is a CAS authentication package for Laravel 4.

**Please be aware that this package is in a early development stage and is not very well tested!**

## Installation

Add requirement of *teuz/laravel-cas* to your composer.json file:

```php
"require": {
	"laravel/framework": "4.2.*",
	"teuz/laravel-cas": "dev-master"
},
```

Update composer using a terminal:

```
composer update
```

Add service providers to *app/config/app.php*: **(Note that both service providers below are required)**

```php
'Teuz\LaravelCas\LaravelCasServiceProvider',
'\anlutro\cURL\Laravel\cURLServiceProvider',
```

**(Recommended)** Add alias to *app/config/app.php*:

```
'Cas' => 'Teuz\LaravelCas\Facades\Cas'
```

Publish package config using a terminal:

```
php artisan config:publish teuz/laravel-cas
```

## Configuration

The configuration file is located at *app/config/packages/teuz/laravel-cas/config.php*.

There are four parameters to configure:
 * url 			- The url to the CAS server (e.g. "https://hostname.com/cas")
 * service 		- Service url that is sent to the CAS-server. This should point to a route that performs a Cas::check().
 * userField	- Name of the user identifier key shared between the CAS server and user object. (E.g. "username")
 * createUsers	- Whether or not to create users. If false, missing users in the database will not be logged in even though they are correctly validated by the CAS  erver.

## Usage

There are four functions of importance: *login*, *check*, *reload* and *logout*.

### Login

The login function should be called when you want the user to be redirected to the CAS login page.
One approach is to add this call to the auth callback:

In *app/filters.php*:

```php
Route::filter('auth', function()
{
	if (Auth::guest())
	{
		return Cas::login();
	}
});
```

Everytime you add this filter to a route, user that are not logged in will be redirected to the CAS login page.

### Check and reload

The check function is used to validate CAS tickets. A route should be added that calls this function:

```php
Route::get('/user/cas', function()
{
	if(Cas::check()) return Cas::reload();
});
```

**Note that this path should be equal to the configuration parameter *service*.**
When the user submits the login form at the CAS server, the user will be redirected to this route with a ticket.
We must then validate the ticket, and if we successfully do so redirect the user to the intended page (using the *reload* function).

### Logout

The function used to logout the user:

```php
Route::get('/user/logout', function()
{
	if(Auth::guest() === false)
	{
		return Cas::logout();
	}
	else
	{
		return Redirect::to('/');
	}
});
```

## License

Copyright 2014 Teuz

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
