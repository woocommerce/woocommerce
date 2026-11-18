<?php
/**
 * Customers query tests.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\ExtensibleAbilities;
use Automattic\WooCommerce\Admin\API\Reports\Customers\DataStore as CustomersDataStore;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\Abilities\AbilitiesLoader;
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
	 * An administrator that every test acts as.
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
	 * Set up an authorized caller.
	 */
	public function setUp(): void {
		parent::setUp();
		wp_set_current_user( self::$user_id );
		$this->sut = new \WP_Ability( CustomersQuery::get_name(), CustomersQuery::get_registration_args() );
	}

	/**
	 * Create a registered customer with personal data on every field that holds it.
	 *
	 * @return array{0: int, 1: int} Analytics customer ID and user ID.
	 */
	private function registered_customer(): array {
		$user_id  = self::factory()->user->create(
			array(
				'role'       => 'customer',
				'user_login' => 'private-login-' . wp_generate_password( 6, false ),
				'user_email' => 'private-' . wp_generate_password( 6, false ) . '@example.test',
			)
		);
		$customer = new \WC_Customer( $user_id );
		$customer->set_first_name( 'Privatefirst' );
		$customer->set_last_name( 'Privatelast' );
		$customer->set_billing_address_1( 'Private Street 1' );
		$customer->set_billing_city( 'Privatecity' );
		$customer->set_billing_phone( '555-0100' );
		$customer->set_billing_email( 'private-billing@example.test' );
		$customer->set_shipping_address_1( 'Private Street 2' );
		$customer->save();
		CustomersDataStore::update_registered_customer( $user_id );
		return array( (int) CustomersDataStore::get_customer_id_by_user_id( $user_id ), $user_id );
	}

	/**
	 * Create an order without line items; the query reads only its Analytics rows. Call
	 * sync_analytics() before querying.
	 *
	 * @param int    $user_id User ID, or 0 for a guest.
	 * @param string $status  Order status.
	 * @param string $email   Billing email of a guest order.
	 * @return \WC_Order
	 */
	private function order( int $user_id, string $status = OrderStatus::COMPLETED, string $email = '' ): \WC_Order {
		$order = wc_create_order(
			array(
				'customer_id' => $user_id,
				'status'      => $status,
			)
		);
		if ( '' !== $email ) {
			$order->set_billing_email( $email );
			$order->set_billing_first_name( 'Privateguest' );
			$order->set_billing_phone( '555-0199' );
		}
		$order->set_total( '10.00' );
		$order->save();
		return $order;
	}

	/**
	 * Run the Analytics import of every order created so far.
	 */
	private function sync_analytics(): void {
		\WC_Helper_Queue::run_all_pending( 'wc-admin-data' );
	}

	/**
	 * Create guests with one completed order each, in one Analytics import.
	 *
	 * @param int $count Number of guests.
	 * @return array<int, int> Analytics customer IDs.
	 */
	private function guest_customers( int $count ): array {
		$emails = array();
		for ( $i = 0; $i < $count; $i++ ) {
			$emails[] = 'private-guest-' . wp_generate_password( 6, false ) . '@example.test';
			$this->order( 0, OrderStatus::COMPLETED, end( $emails ) );
		}
		$this->sync_analytics();
		return array_map( static fn( string $email ) => (int) CustomersDataStore::get_guest_id_by_email( $email ), $emails );
	}

	/**
	 * @testdox Should count the orders Analytics counts, with no personal data anywhere in the output.
	 */
	public function test_returns_spend_without_personal_data(): void {
		list( $id, $user_id ) = $this->registered_customer();
		$completed            = \WC_Helper_Order::create_order( $user_id );
		$completed->set_status( OrderStatus::COMPLETED );
		$completed->save();
		$this->order( $user_id, OrderStatus::FAILED );
		list( $guest ) = $this->guest_customers( 1 );

		$result = $this->sut->execute( array( 'include' => array( $id, $guest ) ) );

		$this->assertIsArray( $result );
		$this->assertSame( 2, $result['total'] );
		$items = array_column( $result['customers'], null, 'id' );
		$this->assertSame( $user_id, $items[ $id ]['user_id'] );
		$this->assertSame( 'customer', $items[ $id ]['role'] );
		$this->assertSame( 1, $items[ $id ]['order_count'], 'A failed order is excluded from Analytics by default.' );
		$this->assertSame( wc_format_decimal( $completed->get_total(), 2 ), $items[ $id ]['total_spent'] );
		$this->assertArrayHasKey( 'date_last_order', $items[ $id ] );
		$this->assertSame( 1, $items[ $guest ]['order_count'] );
		$this->assertArrayNotHasKey( 'user_id', $items[ $guest ], 'A guest has no user.' );
		$this->assertArrayNotHasKey( 'role', $items[ $guest ] );

		$allowed = array( 'id', 'user_id', 'role', 'order_count', 'total_spent', 'date_registered', 'date_last_active', 'date_last_order' );
		foreach ( $items as $item ) {
			$this->assertSame( array(), array_diff( array_keys( $item ), $allowed ), 'A new customer field must be checked for personal data before it joins the allowlist.' );
		}
		$json = (string) wp_json_encode( $result );
		foreach ( array( 'private', 'Private', '555-01', '@' ) as $needle ) {
			$this->assertStringNotContainsString( $needle, $json );
		}
	}

	/**
	 * @testdox Should list registered customers, guests or both by type.
	 */
	public function test_type_filter(): void {
		list( $registered ) = $this->registered_customer();
		list( $guest )      = $this->guest_customers( 1 );
		$input              = array( 'include' => array( $registered, $guest ) );

		$this->assertEqualsCanonicalizing( array( $registered, $guest ), array_column( $this->sut->execute( $input )['customers'], 'id' ) );
		$this->assertSame( array( $registered ), array_column( $this->sut->execute( $input + array( 'type' => 'registered' ) )['customers'], 'id' ) );
		$this->assertSame( array( $guest ), array_column( $this->sut->execute( $input + array( 'type' => 'guest' ) )['customers'], 'id' ) );
	}

	/**
	 * @testdox Should page by customer ID in both directions and count all matches across pages.
	 */
	public function test_paging(): void {
		$ascending = array_merge( array( $this->registered_customer()[0], $this->registered_customer()[0] ), $this->guest_customers( 1 ) );
		sort( $ascending );

		$directions = array(
			'asc'  => $ascending,
			'desc' => array_reverse( $ascending ),
		);
		foreach ( $directions as $order => $ids ) {
			$pages = array();
			for ( $page = 1; $page <= 2; $page++ ) {
				$result = $this->sut->execute(
					array(
						'include'  => $ids,
						'orderby'  => 'id',
						'order'    => $order,
						'per_page' => 2,
						'page'     => $page,
					)
				);
				$this->assertSame( 3, $result['total'], "Total, {$order}." );
				$this->assertSame( 2, $result['total_pages'], "Total pages, {$order}." );
				$pages = array_merge( $pages, array_column( $result['customers'], 'id' ) );
			}
			$this->assertSame( $ids, $pages, "Pages, {$order}." );
		}
	}

	/**
	 * @testdox Should report the matching total on a page past the last one, and zero when nothing matches.
	 */
	public function test_page_past_last_keeps_total(): void {
		$ids = $this->guest_customers( 3 );

		$result = $this->sut->execute(
			array(
				'include'  => $ids,
				'orderby'  => 'id',
				'per_page' => 2,
				'page'     => 5,
			)
		);
		$this->assertSame( array(), $result['customers'] );
		$this->assertSame( 3, $result['total'] );
		$this->assertSame( 2, $result['total_pages'] );

		$result = $this->sut->execute(
			array(
				'include' => array( max( $ids ) + 1000 ),
				'page'    => 2,
			)
		);
		$this->assertSame( 0, $result['total'] );
		$this->assertSame( 0, $result['total_pages'] );
	}

	/**
	 * @testdox Should find customers by name, email and user ID without returning the name or email.
	 */
	public function test_search_and_user_id(): void {
		list( $id, $user_id ) = $this->registered_customer();
		list( $other )        = $this->registered_customer();
		$guest                = $this->guest_customers( 1 )[0];
		$email                = get_userdata( $user_id )->user_email;
		$ids                  = static fn( array $result ): array => array_column( $result['customers'], 'id' );

		$this->assertSame( array( $guest ), $ids( $this->sut->execute( array( 'search' => 'Privateguest' ) ) ), 'A guest is found by the name on their orders.' );
		$this->assertEqualsCanonicalizing( array( $id, $other ), $ids( $this->sut->execute( array( 'search' => 'privatefirst privatelast' ) ) ) );

		$result = $this->sut->execute(
			array(
				'search'    => $email,
				'search_by' => 'email',
			)
		);
		$this->assertSame( array( $id ), $ids( $result ) );
		$this->assertStringNotContainsString( $email, (string) wp_json_encode( $result ) );
		$this->assertSame( array(), $ids( $this->sut->execute( array( 'search' => $email ) ) ), 'search_by defaults to the name.' );

		$result = $this->sut->execute( array( 'search' => '   ' ) );
		$this->assertSame( array(), $result['customers'], 'A whitespace-only search matches no one, not everyone.' );
		$this->assertSame( 0, $result['total'] );

		$this->assertSame( 'woocommerce_ability_customer_search_unsupported', $this->sut->execute( array( 'search' => '0' ) )->get_error_code(), 'A search of "0" is refused rather than read as no search.' );

		$encoded_user = self::factory()->user->create(
			array(
				'role'       => 'customer',
				'user_email' => 'orders%41@example.test',
			)
		);
		$plain_user   = self::factory()->user->create(
			array(
				'role'       => 'customer',
				'user_email' => 'orders@example.test',
			)
		);
		CustomersDataStore::update_registered_customer( $encoded_user );
		CustomersDataStore::update_registered_customer( $plain_user );
		$result = $this->sut->execute(
			array(
				'search'    => 'orders%41@',
				'search_by' => 'email',
			)
		);
		$this->assertSame( array( (int) CustomersDataStore::get_customer_id_by_user_id( $encoded_user ) ), $ids( $result ), 'An email search keeps its literal characters.' );

		$this->assertSame( array( $id ), $ids( $this->sut->execute( array( 'user_id' => $user_id ) ) ) );
		$this->assertSame(
			array(),
			$ids(
				$this->sut->execute(
					array(
						'user_id' => $user_id,
						'type'    => 'guest',
					)
				)
			)
		);
	}

	/**
	 * @testdox Should sort by total spent.
	 */
	public function test_orderby_total_spent(): void {
		list( $big, $big_user ) = $this->registered_customer();
		$this->order( $big_user );
		$this->order( $big_user );
		list( $small ) = $this->guest_customers( 1 );

		$result = $this->sut->execute(
			array(
				'include' => array( $small, $big ),
				'orderby' => 'total_spent',
			)
		);
		$this->assertSame( array( $big, $small ), array_column( $result['customers'], 'id' ) );
	}

	/**
	 * @testdox Should return the IDs in include in one page, minus the IDs in exclude.
	 */
	public function test_include_and_exclude(): void {
		$ids = $this->guest_customers( 6 );
		for ( $i = 0; $i < 6; $i++ ) {
			$ids[] = $this->registered_customer()[0];
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

		$result = $this->sut->execute(
			array(
				'include' => array( $ids[0] ),
				'exclude' => array( $ids[0] ),
			)
		);
		$this->assertSame( array(), $result['customers'] );
		$this->assertSame( 0, $result['total'] );
	}

	/**
	 * @testdox Should skip a lookup row whose user was deleted, while total still counts it.
	 */
	public function test_skips_stale_lookup_row(): void {
		global $wpdb;
		list( $id ) = $this->registered_customer();
		$wpdb->insert(
			CustomersDataStore::get_db_table_name(),
			array(
				'user_id'         => self::$user_id + 100000,
				'date_registered' => '2026-01-01 00:00:00',
			)
		);
		$stale = (int) $wpdb->insert_id;

		$result = $this->sut->execute( array( 'include' => array( $id, $stale ) ) );
		$this->assertSame( array( $id ), array_column( $result['customers'], 'id' ) );
		$this->assertSame( 2, $result['total'] );
	}

	/**
	 * @testdox Should add extension fields and filters to registered customers only, and only while the extensible abilities feature is on.
	 */
	public function test_extension_fields_and_filters(): void {
		$saved = array();
		foreach ( array( 'resources', 'fields', 'filters' ) as $name ) {
			$property = new \ReflectionProperty( ExtensibleAbilities::class, $name );
			$property->setAccessible( true );
			$saved[ $name ] = $property->getValue();
			$property->setValue( null, array() );
		}
		try {
			update_option( 'woocommerce_feature_extensible_abilities_enabled', 'no' );
			$registered = array( $this->registered_customer(), $this->registered_customer() );
			$guest      = $this->guest_customers( 1 )[0];
			$input      = array( 'include' => array( $registered[0][0], $registered[1][0], $guest ) );
			$baseline   = CustomersQuery::execute( $input );
			$schema     = CustomersQuery::get_registration_args()['output_schema'];

			update_option( 'woocommerce_feature_extensible_abilities_enabled', 'yes' );
			add_action( 'woocommerce_extensible_abilities_resources_init', array( AbilitiesLoader::class, 'register_core_resources' ), 0 );
			do_action( 'woocommerce_extensible_abilities_resources_init' );
			$member = $registered[1][1];
			add_action(
				'woocommerce_extensible_abilities_init',
				static function () use ( $member ) {
					ExtensibleAbilities::register_field(
						array(
							'resource'      => 'customer',
							'namespace'     => 'test-ext',
							'field'         => 'tier',
							'output_schema' => array( 'type' => 'string' ),
							'get_callback'  => static fn( \WC_Customer $customer ) => 'tier-' . $customer->get_id(),
						)
					);
					ExtensibleAbilities::register_query_filter(
						array(
							'resource'  => 'customer',
							'namespace' => 'test-ext',
							'filter'    => 'members',
							'schema'    => array( 'type' => 'boolean' ),
							'callback'  => static fn( bool $members ) => $members ? array( 'include_ids' => array( $member ) ) : array( 'exclude_ids' => array( $member ) ),
						)
					);
				}
			);
			do_action( 'woocommerce_extensible_abilities_init' );
			$ability = new \WP_Ability( CustomersQuery::get_name(), CustomersQuery::get_registration_args() );

			$result = $ability->execute( $input );
			$items  = array_column( $result['customers'], null, 'id' );
			$this->assertCount( 3, $items );
			foreach ( $registered as list( $id, $user_id ) ) {
				$this->assertSame( 'tier-' . $user_id, $items[ $id ]['extensions']['test-ext']['tier'] );
			}
			$this->assertArrayNotHasKey( 'extensions', $items[ $guest ], 'A guest has no WC_Customer for extensions to read.' );
			$this->assertSame( 'string', $ability->get_output_schema()['properties']['customers']['items']['properties']['extensions']['properties']['test-ext']['properties']['tier']['type'] );

			$members = static fn( bool $value ) => $input + array( 'filters' => array( 'extensions' => array( 'test-ext' => array( 'members' => $value ) ) ) );
			$this->assertSame( array( $registered[1][0] ), array_column( $ability->execute( $members( true ) )['customers'], 'id' ), 'An extension filter narrows include instead of adding to it.' );
			$this->assertSame( 'woocommerce_ability_extension_filter_failed', $ability->execute( $members( false ) )->get_error_code(), 'A customer filter can only include user IDs.' );

			update_option( 'woocommerce_feature_extensible_abilities_enabled', 'no' );
			$this->assertSame( $baseline, CustomersQuery::execute( $input ) );
			$this->assertSame( $schema, CustomersQuery::get_registration_args()['output_schema'] );
		} finally {
			foreach ( $saved as $name => $value ) {
				$property = new \ReflectionProperty( ExtensibleAbilities::class, $name );
				$property->setAccessible( true );
				$property->setValue( null, $value );
			}
		}
	}

	/**
	 * @testdox Should refuse to run while Analytics is off, because the lookup table is not kept in sync.
	 */
	public function test_refuses_without_analytics(): void {
		update_option( 'woocommerce_analytics_enabled', 'no' );

		$result = $this->sut->execute();

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_ability_customer_analytics_disabled', $result->get_error_code() );
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
