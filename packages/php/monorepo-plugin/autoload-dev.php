<?php declare( strict_types=1 );

( function() {
	// Ensure we are hooking up after primary autoloading being available.
	if ( class_exists( \Automattic\WooCommerce\Autoloader::class, false ) ) {
		// Locate the reference point first - WooCommerce autoloader in our case.
		$reference_point = ( new \ReflectionClass( \Automattic\WooCommerce\Autoloader::class ) )->getFileName();
		$plugin_path     = dirname( $reference_point, 2 );

		// Load composer manifest for class mapping (we'll pick PRS4 only, as it's where new classes are introduced).
		static $namespaces = [];
		$declarations      = json_decode( file_get_contents( $plugin_path . DIRECTORY_SEPARATOR . 'composer.json' ), true );
		foreach ( $declarations['autoload']['psr-4'] ?? [] as $namespace => $path ) {
			$namespaces[$namespace] = $plugin_path . DIRECTORY_SEPARATOR . $path;
		}
		foreach ( $declarations['autoload-dev']['psr-4'] ?? [] as $namespace => $path ) {
			$namespaces[$namespace] = $plugin_path . DIRECTORY_SEPARATOR . $path;
		}

		// Follow the provided mapping and require the matching file if found it.
		spl_autoload_register( function ( string $class ) use( $namespaces ): bool {
			foreach ( $namespaces as $namespace => $path ) {
				if ( strpos( $class, $namespace ) === 0 ) {
					$file = $path . str_replace( "\\", DIRECTORY_SEPARATOR, str_replace( $namespace, '', $class ) . '.php' );
					if ( file_exists( $file ) ) {
						require_once $file;
						return true;
					}
				}
			}
			return false;
		} );
	}
} )();

