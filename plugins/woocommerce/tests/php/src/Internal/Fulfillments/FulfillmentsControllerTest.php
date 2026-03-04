<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Fulfillments;

use Automattic\WooCommerce\Internal\DataStores\Fulfillments\FulfillmentsDataStore;
use Automattic\WooCommerce\Internal\Fulfillments\FulfillmentsController;
use WC_Unit_Test_Case;

/**
 * Tests for the FulfillmentsController class.
 */
class FulfillmentsControllerTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var FulfillmentsController
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = wc_get_container()->get( FulfillmentsController::class );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		update_option( 'woocommerce_feature_fulfillments_enabled', 'no' );
		parent::tearDown();
	}

	/**
	 * @testdox Should register the order-fulfillment data store when feature is enabled.
	 */
	public function test_register_data_stores_when_feature_enabled(): void {
		update_option( 'woocommerce_feature_fulfillments_enabled', 'yes' );

		$result = $this->sut->register_data_stores( array() );

		$this->assertArrayHasKey( 'order-fulfillment', $result, 'Data store should be registered when feature is enabled' );
		$this->assertSame( FulfillmentsDataStore::class, $result['order-fulfillment'], 'Data store class should be FulfillmentsDataStore' );
	}

	/**
	 * @testdox Should not register the order-fulfillment data store when feature is disabled.
	 */
	public function test_register_data_stores_when_feature_disabled(): void {
		update_option( 'woocommerce_feature_fulfillments_enabled', 'no' );

		$result = $this->sut->register_data_stores( array() );

		$this->assertArrayNotHasKey( 'order-fulfillment', $result, 'Data store should not be registered when feature is disabled' );
	}

	/**
	 * @testdox Should preserve existing data stores when feature is enabled.
	 */
	public function test_register_data_stores_preserves_existing_stores(): void {
		update_option( 'woocommerce_feature_fulfillments_enabled', 'yes' );
		$existing = array( 'some-store' => 'SomeStoreClass' );

		$result = $this->sut->register_data_stores( $existing );

		$this->assertArrayHasKey( 'some-store', $result, 'Existing data stores should be preserved' );
		$this->assertSame( 'SomeStoreClass', $result['some-store'] );
		$this->assertArrayHasKey( 'order-fulfillment', $result );
	}

	/**
	 * @testdox Should preserve existing data stores when feature is disabled.
	 */
	public function test_register_data_stores_preserves_existing_stores_when_disabled(): void {
		update_option( 'woocommerce_feature_fulfillments_enabled', 'no' );
		$existing = array( 'some-store' => 'SomeStoreClass' );

		$result = $this->sut->register_data_stores( $existing );

		$this->assertArrayHasKey( 'some-store', $result, 'Existing data stores should be preserved' );
		$this->assertArrayNotHasKey( 'order-fulfillment', $result );
	}

	/**
	 * @testdox Should return non-array input unchanged.
	 */
	public function test_register_data_stores_returns_non_array_unchanged(): void {
		$result = $this->sut->register_data_stores( 'not-an-array' );

		$this->assertSame( 'not-an-array', $result, 'Non-array input should be returned unchanged' );
	}
}
