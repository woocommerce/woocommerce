<?php

declare(strict_types=1);

if ( PHP_VERSION_ID < 80100 ) {
	fwrite(
		STDERR,
		sprintf(
			"Error: PHP 8.1 or later is required. Current version: %s.\n",
			PHP_VERSION
		)
	);
	exit( 2 );
}

require_once __DIR__ . '/../../../../../vendor/autoload.php';

use Automattic\WooCommerce\Internal\Api\DesignTime\Scripts\StalenessChecker;

if ( StalenessChecker::is_stale() ) {
	fwrite( STDERR, "ERROR: Generated GraphQL API code is out of date.\n" );
	fwrite( STDERR, "Run 'pnpm run build:api' to regenerate.\n" );
	exit( 1 );
}

echo "GraphQL API code is up to date.\n";
exit( 0 );
