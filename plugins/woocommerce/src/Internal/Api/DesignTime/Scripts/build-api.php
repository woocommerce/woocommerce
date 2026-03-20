<?php

declare(strict_types=1);

if ( PHP_VERSION_ID < 80100 ) {
	fwrite(
		STDERR,
		sprintf(
			"Error: PHP 8.1 or later is required to run the API build script. Current version: %s.\n",
			PHP_VERSION
		)
	);
	exit( 2 );
}

require_once __DIR__ . '/../../../../../vendor/autoload.php';

use Automattic\WooCommerce\Internal\Api\DesignTime\Scripts\ApiBuilder;

$skip_linter = in_array( '--no-linter', $argv, true );

$builder = new ApiBuilder();
$builder->build( $skip_linter );
