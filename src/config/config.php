<?php

/**
 * Configuration for teuz/laravel-cas
 */
return array(

	/**
	 * CAS-server url
	 */
	'url' => 'https://login.liu.se/cas',

	/**
	 * Callback service url
	 */
	'service' => '/user/cas',

	/**
	 * The user identifier shared between the CAS-server and the user object.
	 */
	'userField' => 'username',

	/**
	 * Whether or not to create missing users.
	 */
	'createUsers' => true

);
