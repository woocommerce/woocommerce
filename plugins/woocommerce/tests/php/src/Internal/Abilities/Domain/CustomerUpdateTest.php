<?php
/**
 * Customer update tests.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities\Domain;

use Automattic\WooCommerce\Admin\API\Reports\Customers\DataStore as CustomersDataStore;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\Abilities\Domain\CustomerUpdate;

/**
 * Tests the customer-update ability.
 */
class CustomerUpdateTest extends \WC_Unit_Test_Case {

	/**
	 * An administrator, who may edit users.
	 *
	 * @var int
	 */
	private static $user_id;

	/**
	 * Create the fixtures that no test changes beyond its own transaction.
	 *
	 * @param \WP_UnitTest_Factory $factory WordPress unit test factory.
	 */
	public static function wpSetUpBeforeClass( $factory ): void {
		self::$user_id = $factory->user->create( array( 'role' => 'administrator' ) );
	}

	/**
	 * Log in as the administrator.
	 */
	public function setUp(): void {
		parent::setUp();
		wp_set_current_user( self::$user_id );
	}

	/**
	 * Run the ability as it is registered.
	 *
	 * @param array $input Ability input.
	 * @return mixed
	 */
	private function execute( array $input ) {
		return wp_get_ability( CustomerUpdate::ABILITY_NAME )->execute( $input );
	}

	/**
	 * Create a registered customer in the Analytics lookup table.
	 *
	 * @return array{0: int, 1: int} Analytics customer ID and user ID.
	 */
	private function registered_customer(): array {
		$user_id  = self::factory()->user->create( array( 'role' => 'customer' ) );
		$customer = new \WC_Customer( $user_id );
		$customer->set_billing_country( 'US' );
		$customer->set_billing_state( 'CA' );
		$customer->set_billing_postcode( '94110' );
		$customer->set_billing_city( 'Old city' );
		$customer->save();
		CustomersDataStore::update_registered_customer( $user_id );
		return array( (int) CustomersDataStore::get_customer_id_by_user_id( $user_id ), $user_id );
	}

	/**
	 * @testdox Should change the names and addresses, and list the changed fields without their values.
	 */
	public function test_updates_names_and_addresses(): void {
		list( $id, $user_id ) = $this->registered_customer();

		$result = $this->execute(
			array(
				'id'         => $id,
				'first_name' => 'Ana',
				'billing'    => array(
					'city'     => 'San Francisco',
					'postcode' => '94110',
				),
				'shipping'   => array(
					'country'  => 'GB',
					'postcode' => 'sw1a1aa',
				),
			)
		);

		$this->assertIsArray( $result );
		$this->assertSame( $id, $result['customer']['id'] );
		$this->assertSame( $user_id, $result['customer']['user_id'] );
		$this->assertEqualsCanonicalizing( array( 'first_name', 'billing.city', 'shipping.country', 'shipping.postcode' ), $result['customer']['updated_fields'], 'A field sent with its saved value did not change.' );
		$this->assertStringNotContainsString( 'San Francisco', (string) wp_json_encode( $result ) );

		$customer = new \WC_Customer( $user_id );
		$this->assertSame( 'Ana', $customer->get_first_name() );
		$this->assertSame( 'San Francisco', $customer->get_billing_city() );
		$this->assertSame( 'CA', $customer->get_billing_state(), 'A field that is not sent keeps its value.' );
		$this->assertSame( 'SW1A 1AA', $customer->get_shipping_postcode(), 'The postcode is formatted for its country.' );
	}

	/**
	 * @testdox Should refuse every invalid address field in one error and save nothing.
	 */
	public function test_refuses_invalid_address_fields(): void {
		list( $id, $user_id ) = $this->registered_customer();

		$result = $this->execute(
			array(
				'id'         => $id,
				'first_name' => 'Ana',
				'billing'    => array(
					'state' => 'XX',
					'email' => 'not-an-email',
				),
				'shipping'   => array( 'country' => 'ZZ' ),
			)
		);

		$this->assertWPError( $result );
		$this->assertEqualsCanonicalizing(
			array( 'woocommerce_ability_customer_billing_state_invalid', 'woocommerce_ability_customer_billing_email_invalid', 'woocommerce_ability_customer_shipping_country_invalid' ),
			$result->get_error_codes()
		);
		$this->assertSame( '', ( new \WC_Customer( $user_id ) )->get_first_name() );
	}

	/**
	 * @testdox Should not quote the saved state and postcode when they fail against a sent country.
	 */
	public function test_refusal_does_not_quote_saved_values(): void {
		list( $id ) = $this->registered_customer();

		$result = $this->execute(
			array(
				'id'      => $id,
				'billing' => array( 'country' => 'CA' ),
			)
		);

		$this->assertWPError( $result );
		$this->assertEqualsCanonicalizing( array( 'woocommerce_ability_customer_billing_state_invalid', 'woocommerce_ability_customer_billing_postcode_invalid' ), $result->get_error_codes() );
		$messages = implode( ' ', $result->get_error_messages() );
		$this->assertStringNotContainsString( '94110', $messages );
		$this->assertStringNotContainsString( '"', $messages, 'Only sent values are quoted.' );
	}

	/**
	 * @testdox Should report a lookup row whose user is gone as an unknown customer.
	 */
	public function test_stale_lookup_row_is_not_found(): void {
		global $wpdb;
		$wpdb->insert(
			CustomersDataStore::get_db_table_name(),
			array(
				'user_id'         => self::$user_id + 100000,
				'date_registered' => '2026-01-01 00:00:00',
			)
		);
		$id = (int) $wpdb->insert_id;

		$this->assertTrue( CustomerUpdate::can_edit_customer( array( 'id' => $id ) ) );
		$result = $this->execute(
			array(
				'id'         => $id,
				'first_name' => 'Ana',
			)
		);
		$this->assertSame( 'woocommerce_ability_customer_not_found', $result->get_error_code() );
	}

	/**
	 * @testdox Should refuse a guest, an unknown customer and a call with nothing to change.
	 */
	public function test_refuses_guests_unknown_ids_and_empty_changes(): void {
		$order = wc_create_order( array( 'status' => OrderStatus::COMPLETED ) );
		$order->set_billing_email( 'guest@example.test' );
		$order->save();
		\WC_Helper_Queue::run_all_pending( 'wc-admin-data' );
		$guest = (int) CustomersDataStore::get_guest_id_by_email( 'guest@example.test' );

		$result = $this->execute(
			array(
				'id'         => $guest,
				'first_name' => 'Ana',
			)
		);
		$this->assertSame( 'woocommerce_ability_customer_guest', $result->get_error_code() );

		$result = $this->execute(
			array(
				'id'         => $guest + 1000,
				'first_name' => 'Ana',
			)
		);
		$this->assertSame( 'woocommerce_ability_customer_not_found', $result->get_error_code() );

		$result = $this->execute( array( 'id' => $this->registered_customer()[0] ) );
		$this->assertSame( 'woocommerce_ability_customer_no_fields', $result->get_error_code() );
	}

	/**
	 * @testdox Should let a shop manager edit a customer but not an administrator.
	 */
	public function test_permissions(): void {
		list( $id )    = $this->registered_customer();
		$admin_user_id = get_current_user_id();
		add_filter( 'woocommerce_analytics_customer_roles', static fn() => array( 'customer', 'administrator' ) );
		CustomersDataStore::update_registered_customer( $admin_user_id );
		$admin = (int) CustomersDataStore::get_customer_id_by_user_id( $admin_user_id );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'shop_manager' ) ) );
		$this->assertTrue( CustomerUpdate::can_edit_customer( array( 'id' => $id ) ) );
		$this->assertFalse( CustomerUpdate::can_edit_customer( array( 'id' => $admin ) ) );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'customer' ) ) );
		$this->assertFalse( CustomerUpdate::can_edit_customer( array( 'id' => $id ) ) );
	}

	/**
	 * @testdox Should refuse to run while Analytics is off.
	 */
	public function test_refuses_without_analytics(): void {
		list( $id ) = $this->registered_customer();
		update_option( 'woocommerce_analytics_enabled', 'no' );

		$result = $this->execute(
			array(
				'id'         => $id,
				'first_name' => 'Ana',
			)
		);

		$this->assertSame( 'woocommerce_ability_customer_analytics_disabled', $result->get_error_code() );
	}
}
