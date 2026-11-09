<?php
/**
 * Customers query tests.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities\Domain;

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\Abilities\Domain\CustomersQuery;

/**
 * Tests customer queries through the Abilities API.
 */
class CustomersQueryTest extends \WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var \WP_Ability
	 */
	private $sut;

	/**
	 * Set up an authorized caller.
	 */
	public function setUp(): void {
		parent::setUp();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$this->sut = new \WP_Ability( CustomersQuery::get_name(), CustomersQuery::get_registration_args() );
	}

	/**
	 * Create a customer with personal data on every field that holds it.
	 *
	 * @param string $role User role.
	 * @return int
	 */
	private function customer( string $role = 'customer' ): int {
		$id       = self::factory()->user->create(
			array(
				'role'       => $role,
				'user_login' => 'private-login-' . wp_generate_password( 6, false ),
				'user_email' => 'private-' . wp_generate_password( 6, false ) . '@example.test',
			)
		);
		$customer = new \WC_Customer( $id );
		$customer->set_first_name( 'Privatefirst' );
		$customer->set_last_name( 'Privatelast' );
		$customer->set_billing_address_1( 'Private Street 1' );
		$customer->set_billing_phone( '555-0100' );
		$customer->set_billing_email( 'private-billing@example.test' );
		$customer->set_shipping_address_1( 'Private Street 2' );
		$customer->save();
		return $id;
	}

	/**
	 * @testdox Should return order count and total spent with no personal data anywhere in the output.
	 */
	public function test_returns_spend_without_personal_data(): void {
		$id    = $this->customer();
		$order = \WC_Helper_Order::create_order( $id );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();
		\WC_Helper_Order::create_order( $id );

		$result = $this->sut->execute( array( 'include' => array( $id ) ) );

		$this->assertIsArray( $result );
		$this->assertSame( 1, $result['total'] );
		$item = $result['customers'][0];
		$this->assertSame( $id, $item['id'] );
		$this->assertSame( 'customer', $item['role'] );
		$this->assertSame( 2, $item['order_count'], 'Every order counts, paid or not.' );
		$this->assertSame( wc_format_decimal( $order->get_total(), 2 ), $item['total_spent'], 'Only the paid order adds to the spend.' );
		$allowed = array( 'id', 'role', 'is_paying_customer', 'order_count', 'total_spent', 'date_created', 'date_modified' );
		$this->assertSame( array(), array_diff( array_keys( $item ), $allowed ), 'A new customer field must be checked for personal data before it joins the allowlist.' );
		$json = (string) wp_json_encode( $result );
		foreach ( array( 'private', 'Private', '555-0100', '@' ) as $needle ) {
			$this->assertStringNotContainsString( $needle, $json );
		}
	}

	/**
	 * @testdox Should list only customers by default, every user with role all, and count all matches across pages.
	 */
	public function test_role_and_paging(): void {
		$customers = array( $this->customer(), $this->customer(), $this->customer() );
		$this->customer( 'shop_manager' );

		$result = $this->sut->execute(
			array(
				'per_page' => 2,
				'orderby'  => 'id',
				'order'    => 'asc',
			)
		);
		$this->assertSame( 3, $result['total'] );
		$this->assertSame( 2, $result['total_pages'] );
		$this->assertSame( array_slice( $customers, 0, 2 ), array_column( $result['customers'], 'id' ) );

		$result = $this->sut->execute(
			array(
				'per_page' => 2,
				'page'     => 2,
				'orderby'  => 'id',
				'order'    => 'asc',
			)
		);
		$this->assertSame( array( $customers[2] ), array_column( $result['customers'], 'id' ) );

		$this->assertSame( 1, $this->sut->execute( array( 'role' => 'shop_manager' ) )['total'] );
		$this->assertSame( count_users()['total_users'], $this->sut->execute( array( 'role' => 'all' ) )['total'] );
	}

	/**
	 * @testdox Should return the IDs in include in one page, minus the IDs in exclude.
	 */
	public function test_include_and_exclude(): void {
		$ids = array();
		for ( $i = 0; $i < 12; $i++ ) {
			$ids[] = $this->customer();
		}

		$result = $this->sut->execute( array( 'include' => $ids ) );
		$this->assertCount( 12, $result['customers'], 'Without per_page, include comes back in one page.' );
		$this->assertSame( 12, $result['per_page'] );

		$result = $this->sut->execute(
			array(
				'include' => array_slice( $ids, 0, 3 ),
				'exclude' => array( $ids[1] ),
			)
		);
		$this->assertEqualsCanonicalizing( array( $ids[0], $ids[2] ), array_column( $result['customers'], 'id' ) );

		$result = $this->sut->execute( array( 'exclude' => array_slice( $ids, 1 ) ) );
		$this->assertSame( array( $ids[0] ), array_column( $result['customers'], 'id' ) );
	}

	/**
	 * @testdox Should let shop managers read customers and refuse users who cannot list users.
	 */
	public function test_permissions(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'shop_manager' ) ) );
		$this->assertTrue( CustomersQuery::can_query_customers() );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'customer' ) ) );
		$this->assertWPError( $this->sut->execute() );
	}
}
