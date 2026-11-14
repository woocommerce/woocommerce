<?php
/**
 * Customers query tests.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\ExtensibleAbilities;
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
	 * @testdox Should order customers registered in the same second by ID, so pages neither repeat nor skip them.
	 * @testWith ["desc"]
	 *           ["asc"]
	 * @param string $order Sort direction.
	 */
	public function test_same_second_registrations_page_by_id( string $order ): void {
		$ids = array();
		for ( $i = 0; $i < 3; $i++ ) {
			$ids[] = self::factory()->user->create(
				array(
					'role'            => 'customer',
					'user_registered' => '2026-01-01 00:00:00',
				)
			);
		}
		if ( 'desc' === $order ) {
			$ids = array_reverse( $ids );
		}

		$pages = array();
		for ( $page = 1; $page <= 3; $page++ ) {
			$result = $this->sut->execute(
				array(
					'order'    => $order,
					'per_page' => 1,
					'page'     => $page,
				)
			);
			$pages  = array_merge( $pages, array_column( $result['customers'], 'id' ) );
		}
		$this->assertSame( $ids, $pages );
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
	 * @testdox Should add extension fields and filters only while the extensible abilities feature is on.
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
			$ids      = array( $this->customer(), $this->customer(), $this->customer() );
			$input    = array( 'include' => $ids );
			$baseline = CustomersQuery::execute( $input );
			$schema   = CustomersQuery::get_registration_args()['output_schema'];

			update_option( 'woocommerce_feature_extensible_abilities_enabled', 'yes' );
			add_action( 'woocommerce_extensible_abilities_resources_init', array( AbilitiesLoader::class, 'register_core_resources' ), 0 );
			do_action( 'woocommerce_extensible_abilities_resources_init' );
			add_action(
				'woocommerce_extensible_abilities_init',
				static function () use ( $ids ) {
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
							'callback'  => static fn( bool $members ) => array( 'include_ids' => $members ? array_slice( $ids, 1 ) : array( $ids[0] ) ),
						)
					);
				}
			);
			do_action( 'woocommerce_extensible_abilities_init' );
			$ability = new \WP_Ability( CustomersQuery::get_name(), CustomersQuery::get_registration_args() );

			$result = $ability->execute( $input );
			$this->assertCount( 3, $result['customers'] );
			foreach ( $result['customers'] as $customer ) {
				$this->assertSame( 'tier-' . $customer['id'], $customer['extensions']['test-ext']['tier'] );
			}
			$this->assertSame( 'string', $ability->get_output_schema()['properties']['customers']['items']['properties']['extensions']['properties']['test-ext']['properties']['tier']['type'] );

			$result = $ability->execute(
				array(
					'include' => array( $ids[0], $ids[1] ),
					'filters' => array( 'extensions' => array( 'test-ext' => array( 'members' => true ) ) ),
				)
			);
			$this->assertSame( array( $ids[1] ), array_column( $result['customers'], 'id' ), 'An extension filter narrows include instead of adding to it.' );

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
	 * @testdox Should let shop managers read customers and refuse users who cannot list users.
	 */
	public function test_permissions(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'shop_manager' ) ) );
		$this->assertTrue( CustomersQuery::can_query_customers() );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'customer' ) ) );
		$this->assertWPError( $this->sut->execute() );
	}
}
