<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\AgentReadiness\ReadinessCheckPage;
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
	 * @testdox The checkout check, pending until its output ships, has its copy and is Required.
	 */
	public function test_checkout_check_has_copy_and_is_required(): void {
		$copy = ReadinessCopy::store_check( 'ucp_checkout' );
		$this->assertSame( 'Let AI assistants send shoppers to your checkout', $copy['action'] ?? null );
		$this->assertNotSame( '', $copy['why'] );
		$this->assertNotSame( '', $copy['found'] );
		$this->assertSame( 'Turn off AI shopping (also called UCP or agentic commerce) in the other plugin, or deactivate it.', $copy['fix'] );

		$required = new \ReflectionMethod( ReadinessCheckPage::class, 'is_required' );
		$required->setAccessible( true );
		$check = array(
			'id'       => 'ucp_checkout',
			'blocks'   => 'ucp',
			'group'    => 'access',
			'level'    => 'recommended',
			'conflict' => false,
		);
		$this->assertTrue( $required->invoke( null, $check ), 'Without checkout, AI assistants find products but can\'t sell them' );
		$this->assertFalse( $required->invoke( null, array( 'id' => 'ucp_profile_version' ) + $check ), 'Other UCP checks stay recommended' );
	}

	/**
	 * @testdox Every product check in the rules file has copy that says what to do and to how many products.
	 */
	public function test_every_product_check_has_copy(): void {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- A local file in the plugin.
		$rules  = json_decode( (string) file_get_contents( WC_ABSPATH . 'src/Internal/AgentReadiness/rules.json' ), true );
		$hidden = new \ReflectionClassConstant( ReadinessCheckPage::class, 'HIDDEN_PRODUCT_CHECKS' );
		$ids    = array_diff( array_keys( $rules['checks'] ), $hidden->getValue() );

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

	/**
	 * @testdox Each llms.txt source has a line for a served file or a fix for a missing one, and an unknown source has none.
	 */
	public function test_llms_txt_source_copy(): void {
		foreach ( array( 'woocommerce', 'file', 'other' ) as $source ) {
			$this->assertNotEmpty( ReadinessCopy::llms_txt_source( $source )['pass'] ?? '', "{$source} says where llms.txt comes from" );
		}
		foreach ( array( 'disabled', 'not_reached' ) as $source ) {
			$this->assertNotEmpty( ReadinessCopy::llms_txt_source( $source )['fix'] ?? '', "{$source} has its own fix" );
		}
		$this->assertNull( ReadinessCopy::llms_txt_source( '' ) );
		$this->assertNull( ReadinessCopy::llms_txt_source( 'plugin' ) );
	}
}
