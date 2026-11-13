<?php
/**
 * Bootstrap for the PHPStan rule tests. PHPStan reflects test fixtures only through registered autoloaders.
 *
 * @package WooCommerce\Tests
 */

declare( strict_types=1 );

require_once __DIR__ . '/../../../vendor/autoload.php';

spl_autoload_register(
	function ( string $class_name ): void {
		$files = array(
			'WC_Data'               => 'stubs.php',
			'WC_Data_Store'         => 'stubs.php',
			'Stage_Refusal_Fixture' => 'stage-refusal.php',
			'Writes_Fixture'        => 'writes.php',
			'Twins_Fixture'         => 'twins.php',
			'Exempt_Fixture'        => 'exempt.php',
			'Not_WC_Data_Fixture'   => 'exempt.php',
		);

		if ( isset( $files[ $class_name ] ) ) {
			require_once __DIR__ . '/data/' . $files[ $class_name ];
		}
	}
);
