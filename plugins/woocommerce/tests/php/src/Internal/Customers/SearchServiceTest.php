<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Customers;

use Automattic\WooCommerce\Internal\Customers\SearchService as CustomersSearchService;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\CustomerHelper;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use Automattic\WooCommerce\RestApi\UnitTests\HPOSToggleTrait;
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * Tests for `\Automattic\WooCommerce\Internal\Customers\SearchService` class.
 */
class SearchServiceTest extends \WC_Unit_Test_Case {
	use HPOSToggleTrait;

	/**
	 * Service instance.
	 *
	 * @var CustomersSearchService;
	 */
	private CustomersSearchService $service;

	/**
	 * Original HPOS status.
	 *
	 * @var bool
	 */
	private bool $original_hpos_status;

	/**
	 * Modify the testing environment.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->service = wc_get_container()->get( CustomersSearchService::class );

		$this->original_hpos_status = OrderUtil::custom_orders_table_usage_is_enabled();
		if ( ! $this->original_hpos_status ) {
			$this->setup_cot();
		}
	}

	/**
	 * Restore the testing environment.
	 */
	public function tearDown(): void {
		$this->toggle_cot_feature_and_usage( $this->original_hpos_status );

		parent::tearDown();
	}

	/**
	 * Test search when NOT looking up the HPOS order table.
	 *
	 * @return void
	 */
	public function test_find_user_ids_by_billing_email(): void {
		$this->toggle_cot_feature_and_usage( false );

		$customer1 = $this->create_customer( 'customer1', '', 'customer1@example.com' );
		$customer2 = $this->create_customer( 'customer2', '', 'customer2@example.com' );

		$this->assertSame(
			array( $customer1->get_id() ),
			$this->service->find_user_ids_by_billing_email_for_coupons_usage_lookup( array( $customer1->get_billing_email() ) )
		);
		$this->assertSame(
			array( $customer1->get_id(), $customer2->get_id() ),
			$this->service->find_user_ids_by_billing_email_for_coupons_usage_lookup( array( $customer1->get_billing_email(), $customer2->get_billing_email() ) )
		);

		$customer1->delete( true );
		$customer2->delete( true );
	}

	/**
	 * Test search when looking up the HPOS order table.
	 *
	 * @return void
	 */
	public function test_find_user_ids_by_billing_email_with_have_orders_flag(): void {
		$this->toggle_cot_feature_and_usage( true );

		$customer1 = $this->create_customer( 'customer1', '', 'customer1@example.com' );
		$customer2 = $this->create_customer( 'customer2', '', 'customer2@example.com' );
		$order     = $this->create_order_for( $customer1 );

		$this->assertSame(
			array( $customer1->get_id() ),
			$this->service->find_user_ids_by_billing_email_for_coupons_usage_lookup( array( $customer1->get_billing_email() ) )
		);
		$this->assertSame(
			array( $customer1->get_id() ),
			$this->service->find_user_ids_by_billing_email_for_coupons_usage_lookup( array( $customer1->get_billing_email(), $customer2->get_billing_email() ) )
		);

		$order->delete( true );
		$customer1->delete( true );
		$customer2->delete( true );
	}

	/**
	 * Wrapper around the helper, as injecting the billing in there cause some tests to fail.
	 *
	 * @param string $username Username.
	 * @param string $password Password.
	 * @param string $email    Email.
	 * @return \WC_Customer
	 */
	private function create_customer( string $username, string $password, string $email ): \WC_Customer {
		$customer = CustomerHelper::create_customer( $username, $password, $email );
		$customer->set_billing_email( $email );
		$customer->save();
		return $customer;
	}

	/**
	 * Wrapper around the helper, as injecting the billing in there cause some tests to fail.
	 *
	 * @param \WC_Customer $customer Customer object.
	 * @return \WC_Order
	 */
	private function create_order_for( \WC_Customer $customer ): \WC_Order {
		$order = OrderHelper::create_order( $customer->get_id() );
		$order->set_billing_email( $customer->get_billing_email( 'edit' ) );
		$order->save();
		return $order;
	}
}
