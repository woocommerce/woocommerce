<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Utilities;

use Automattic\WooCommerce\Internal\Utilities\Types;
use DateTime;
use InvalidArgumentException;
use WC_DateTime;
use WC_Product;
use WC_Unit_Test_Case;
use WP_Error;

/**
 * A collection of tests for the types-handling utility class.
 */
class TypesTest extends WC_Unit_Test_Case {
	/**
	 * Describe basic behaviors of the `Types::ensure_instance_of()` utility method.
	 *
	 * @return void
	 */
	public function test_ensure_instance_of(): void {
		$datetime = new WC_DateTime( 'now' );

		$this->assertInstanceOf(
			WC_DateTime::class,
			Types::ensure_instance_of( $datetime, WC_DateTime::class ),
			'We can validate that an object is of a specific type.'
		);

		$this->assertInstanceOf(
			DateTime::class,
			Types::ensure_instance_of( $datetime, DateTime::class ),
			'We can validate that an object is descended from a specific type.'
		);

		$this->assertInstanceOf(
			WP_Error::class,
			Types::ensure_instance_of( $datetime, WC_Product::class, fn () => new WP_Error() ),
			'Error handling callbacks can be specified to implement custom logic if an unexpected type is encountered.'
		);

		$this->expectException( InvalidArgumentException::class );
		Types::ensure_instance_of( $datetime, WC_Product::class );
	}
}
