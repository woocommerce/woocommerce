<?php
/**
 * Tests for COTMigration utility.
 */

use Automattic\WooCommerce\Internal\Utilities\COTMigrationUtil;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;

/**
 * Tests for COTMigration utility.
 */
class COTMigrationUtilTest extends WC_Unit_Test_Case {

	/**
	 * @var COTMigrationUtil
	 */
	private $sut;

	/**
	 * Set-up subject under test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = wc_get_container()->get( COTMigrationUtil::class );
	}

	/**
	 * Test test_get_post_or_object_meta function with both posts and order objects.
	 */
	public function test_get_post_or_object_meta() {
		$order = OrderHelper::create_order();
		$post  = get_post( $order->get_id() );
		update_post_meta( $order->get_id(), 'dummy_meta', 'dummy_value' );

		$this->assertEquals( 'dummy_value', $this->sut->get_post_or_object_meta( $post, $order, 'dummy_meta', true ) );
		$this->assertEquals( 'dummy_value', $this->sut->get_post_or_object_meta( $post, null, 'dummy_meta', true ) );
	}

	/**
	 * Tests init_theorder_object with both posts and order objects.
	 */
	public function test_init_theorder_object() {
		global $theorder;
		$order1           = OrderHelper::create_order();
		$order2           = OrderHelper::create_order();
		$post_from_order2 = get_post( $order2->get_id() );

		$this->assertEquals( $order1, $this->sut->init_theorder_object( $order1 ) );
		$this->assertEquals( $theorder->get_id(), $order1->get_id() );

		$theorder = null;
		$this->sut->init_theorder_object( $post_from_order2 );
		$this->assertEquals( $theorder->get_id(), $order2->get_id() );
	}

	/**
	 * Tests get_post_or_order_id with both posts and order objects.
	 */
	public function test_get_post_or_order_id() {
		$order1           = OrderHelper::create_order();
		$order2           = OrderHelper::create_order();
		$post_from_order2 = get_post( $order2->get_id() );

		$this->assertEquals( $order1->get_id(), $this->sut->get_post_or_order_id( $order1 ) );
		$this->assertEquals( $order2->get_id(), $this->sut->get_post_or_order_id( $post_from_order2 ) );
	}

}
