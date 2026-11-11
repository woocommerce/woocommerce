<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\AgentReadiness\ReadinessCopy;
use Automattic\WooCommerce\Internal\AgentReadiness\StoreOutputChecks;
use WC_Unit_Test_Case;

/**
 * Tests for ReadinessCopy.
 */
class ReadinessCopyTest extends WC_Unit_Test_Case {

	/**
	 * Checks that wait for output WooCommerce doesn't build yet, so Sales readiness doesn't show them.
	 */
	private const PENDING = array( 'product_return_policy_schema', 'product_shipping_schema', 'product_buy_action_schema', 'ucp_checkout' );

	/**
	 * @testdox Every store check Sales readiness shows has copy, headed by what to do and saying what the scan found.
	 */
	public function test_every_store_check_has_copy(): void {
		$checks = new \ReflectionMethod( StoreOutputChecks::class, 'checks' );
		$checks->setAccessible( true );
		$ids = array_diff( array_column( $checks->invoke( wc_get_container()->get( StoreOutputChecks::class ) ), 'id' ), self::PENDING );

		$this->assertNotEmpty( $ids );
		foreach ( $ids as $id ) {
			$copy = ReadinessCopy::store_check( $id );
			$this->assertNotNull( $copy, "{$id} has copy" );
			$this->assertNotSame( $copy['title'], $copy['action'], "{$id} is worded as what to do while it fails" );
			$this->assertNotSame( '', $copy['why'], "{$id} says why it matters once it passes" );
			$this->assertNotSame( '', $copy['found'], "{$id} says why it matters while it fails" );
		}
		$this->assertNull( ReadinessCopy::store_check( 'not_a_check' ), 'A check without copy keeps the report\'s' );
	}

	/**
	 * @testdox Every product check in the rules file has copy that says what to do and to how many products.
	 */
	public function test_every_product_check_has_copy(): void {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- A local file in the plugin.
		$rules = json_decode( (string) file_get_contents( WC_ABSPATH . 'src/Internal/AgentReadiness/rules.json' ), true );
		$ids   = array_keys( $rules['checks'] );

		$this->assertNotEmpty( $ids );
		foreach ( $ids as $id ) {
			$copy = ReadinessCopy::product_check( $id, 13 );
			$this->assertNotNull( $copy, "{$id} has copy" );
			$this->assertStringContainsString( '13 products', $copy['action'], "{$id} names how many products" );
		}
		$this->assertSame( 'Add a brand to 1 product', ReadinessCopy::product_check( 'missing_brand', 1 )['action'] );
		$this->assertSame( '', ReadinessCopy::product_check( 'missing_brand', 1 )['why'], 'The channels say why a brand matters' );
		$this->assertNull( ReadinessCopy::product_check( 'not_a_check', 1 ) );
	}
}
