<?php

namespace Automattic\WooCommerce\Database;

use Illuminate\Database\Capsule\Manager;

class QueryManager extends Manager {
	private static $_instance;
	public static function instance()
	{
	    if (static::$_instance === null) {
		    $manager = new self();
		    $manager->addConnection( array(
			    'driver'    => 'mysql',
			    'host'      => DB_HOST,
			    'database'  => DB_NAME,
			    'username'  => DB_USER,
			    'password'  => DB_PASSWORD,
		    ) );
		    $manager->setAsGlobal();
			static::$_instance = $manager;
	    }

		return static::$_instance;
	}
}
