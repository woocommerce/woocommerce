<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Customers;

use Automattic\WooCommerce\Internal\Customers\SearchService as CustomerSearchService;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\CustomerHelper;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;

/**
 * Tests for `\Automattic\WooCommerce\Internal\Customers\SearchService` class.
 */
class SearchServiceTest extends \WC_Unit_Test_Case {

	/**
	 * Service instance.
	 *
	 * @var CustomerSearchService;
	 */
	private CustomerSearchService $service;

	/**
	 * Setup.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->service = wc_get_container()->get( CustomerSearchService::class );
	}

	/**
	 * Test search when NOT looking up the HPOS order table.
	 *
	 * @return void
	 */
	public function test_find_user_ids_by_billing_email(): void {
		$customer1 = CustomerHelper::create_customer( 'customer1', '', 'customer1@example.com' );
		$customer2 = CustomerHelper::create_customer( 'customer2', '', 'customer2@example.com' );

		$this->assertSame(
			array( $customer1->get_id() ),
			$this->service->find_user_ids_by_billing_email( array( $customer1->get_billing_email() ) )
		);
		$this->assertSame(
			array( $customer1->get_id(), $customer2->get_id() ),
			$this->service->find_user_ids_by_billing_email( array( $customer1->get_billing_email(), $customer2->get_billing_email() ) )
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
		$customer1 = CustomerHelper::create_customer( 'customer1', '', 'customer1@example.com' );
		$customer2 = CustomerHelper::create_customer( 'customer2', '', 'customer2@example.com' );
		$order     = OrderHelper::create_order( $customer1->get_id() );

		$this->assertSame(
			array( $customer1->get_id() ),
			$this->service->find_user_ids_by_billing_email( array( $customer1->get_billing_email() ), true )
		);
		$this->assertSame(
			array( $customer1->get_id() ),
			$this->service->find_user_ids_by_billing_email( array( $customer1->get_billing_email(), $customer2->get_billing_email() ), true )
		);

		$order->delete( true );
		$customer1->delete( true );
		$customer2->delete( true );
	}
}
