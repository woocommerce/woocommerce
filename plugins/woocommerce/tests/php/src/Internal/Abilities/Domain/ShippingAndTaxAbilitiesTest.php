<?php
/**
 * ShippingAndTaxAbilitiesTest class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities\Domain;

use Automattic\WooCommerce\Internal\Abilities\AbilitiesLoader;

/**
 * Tests for the shipping-zones-query and tax-rates-query abilities.
 */
class ShippingAndTaxAbilitiesTest extends \WC_Unit_Test_Case {

	/**
	 * Administrator used as the current user.
	 *
	 * @var int
	 */
	private static $administrator_id;

	/**
	 * Init action count restored in tearDown.
	 *
	 * @var int|null
	 */
	private $original_init_count;

	/**
	 * Create the administrator once for the class.
	 *
	 * @param \WP_UnitTest_Factory $factory WordPress unit test factory.
	 */
	public static function wpSetUpBeforeClass( $factory ): void {
		self::$administrator_id = $factory->user->create( array( 'role' => 'administrator' ) );
	}

	/**
	 * Start each test from an empty abilities registry, so the abilities register again.
	 */
	public function setUp(): void {
		global $wp_actions, $wpdb;

		if ( ! function_exists( 'wp_register_ability' ) ) {
			require_once WC_ABSPATH . 'vendor/wordpress/abilities-api/includes/bootstrap.php';
		}

		// The Abilities API registry refuses to initialize before `init` has fired.
		$this->original_init_count = $wp_actions['init'] ?? null;
		$wp_actions['init']        = max( 1, (int) ( $wp_actions['init'] ?? 0 ) ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		parent::setUp();
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rate_locations" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rates" );

		wc_get_container()->get( AbilitiesLoader::class )->init();
		self::reset_registries();
		wp_set_current_user( self::$administrator_id );
	}

	/**
	 * Leave the registries and the init count as the next test expects them.
	 */
	public function tearDown(): void {
		global $wp_actions;

		try {
			self::reset_registries();
		} finally {
			parent::tearDown();
		}

		if ( null !== $this->original_init_count ) {
			$wp_actions['init'] = $this->original_init_count; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		} else {
			unset( $wp_actions['init'] ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}
	}

	/**
	 * Run an ability as the current user, with its permission check.
	 *
	 * @param string $name  Ability name.
	 * @param array  $input Ability input.
	 * @return mixed|\WP_Error
	 */
	private function run_ability( string $name, array $input = array() ) {
		$ability = wp_get_ability( $name );
		$this->assertNotNull( $ability, "Ability {$name} should be registered." );

		return $ability->execute( $input );
	}

	/**
	 * Drop every registered ability, so the next read registers them again.
	 */
	private static function reset_registries(): void {
		global $wp_actions;

		foreach ( array( 'WP_Abilities_Registry', 'WP_Ability_Categories_Registry' ) as $registry ) {
			if ( class_exists( $registry ) ) {
				$instance = new \ReflectionProperty( $registry, 'instance' );
				$instance->setAccessible( true );
				$instance->setValue( null, null );
			}
		}
		unset( $wp_actions['wp_abilities_api_init'], $wp_actions['wp_abilities_api_categories_init'] );
	}

	/**
	 * @testdox Should return each zone with its locations and methods, plus the catch-all zone.
	 */
	public function test_zones_include_locations_and_methods(): void {
		$zone = new \WC_Shipping_Zone();
		$zone->set_zone_name( 'Portugal' );
		$zone->add_location( 'PT', 'country' );
		$zone->save();
		$instance_id = $zone->add_shipping_method( 'flat_rate' );
		update_option( "woocommerce_flat_rate_{$instance_id}_settings", array( 'cost' => '4.50' ) );

		$result = $this->run_ability( 'woocommerce/shipping-zones-query' );

		$this->assertNotWPError( $result );
		$by_id = array_column( $result['zones'], null, 'id' );
		$this->assertArrayHasKey( 0, $by_id, 'The catch-all zone should be listed.' );
		$item = $by_id[ $zone->get_id() ];
		$this->assertSame(
			array(
				'code' => 'PT',
				'type' => 'country',
			),
			$item['locations'][0]
		);
		$this->assertSame( 'flat_rate', $item['methods'][0]['method_id'] );
		$this->assertSame( '4.50', ( (array) $item['methods'][0]['settings'] )['cost'] );
	}

	/**
	 * @testdox Should return only the settings Core declares for its methods, not those added through the form fields filter.
	 */
	public function test_core_method_settings_leave_out_added_fields(): void {
		add_filter(
			'woocommerce_shipping_instance_form_fields_flat_rate',
			static function ( array $fields ): array {
				$fields['carrier_account'] = array(
					'title' => 'Carrier account',
					'type'  => 'text',
				);
				return $fields;
			}
		);
		$instance_id = ( new \WC_Shipping_Zone( 0 ) )->add_shipping_method( 'flat_rate' );
		update_option(
			"woocommerce_flat_rate_{$instance_id}_settings",
			array(
				'cost'            => '4.50',
				'carrier_account' => 'canary-account',
				'undeclared'      => 'canary-stale',
			)
		);

		$result   = $this->run_ability( 'woocommerce/shipping-zones-query', array( 'include' => array( 0 ) ) );
		$settings = (array) $result['zones'][0]['methods'][0]['settings'];

		$this->assertSame( '4.50', $settings['cost'] );
		$this->assertArrayNotHasKey( 'carrier_account', $settings );
		$this->assertArrayNotHasKey( 'undeclared', $settings );
		$this->assertStringNotContainsString( 'canary', (string) wp_json_encode( $result ) );
	}

	/**
	 * @testdox Should leave out a field that a zone methods filter adds to a Core method object.
	 */
	public function test_core_method_settings_leave_out_fields_added_to_the_zone_method(): void {
		add_filter(
			'woocommerce_shipping_zone_shipping_methods',
			static function ( $methods ) {
				foreach ( $methods as $method ) {
					$method->instance_form_fields['carrier_account'] = array(
						'title' => 'Carrier account',
						'type'  => 'text',
					);
				}
				return $methods;
			}
		);
		$instance_id = ( new \WC_Shipping_Zone( 0 ) )->add_shipping_method( 'flat_rate' );
		update_option(
			"woocommerce_flat_rate_{$instance_id}_settings",
			array(
				'cost'            => '4.50',
				'carrier_account' => 'canary-account',
			)
		);

		$result   = $this->run_ability( 'woocommerce/shipping-zones-query', array( 'include' => array( 0 ) ) );
		$settings = (array) $result['zones'][0]['methods'][0]['settings'];

		$this->assertSame( '4.50', $settings['cost'] );
		$this->assertArrayNotHasKey( 'carrier_account', $settings );
	}

	/**
	 * @testdox Should return no settings for a method from an extension, even one that extends a Core method.
	 */
	public function test_extension_methods_have_no_settings(): void {
		$carrier  = new class() extends \WC_Shipping_Flat_Rate {
			/**
			 * Give the subclass its own method ID.
			 *
			 * @param int $instance_id Instance ID.
			 */
			public function __construct( $instance_id = 0 ) {
				parent::__construct( $instance_id );
				$this->id = 'test_carrier';
			}
		};
		$register = static function ( array $methods ) use ( $carrier ): array {
			$methods['test_carrier'] = $carrier;
			return $methods;
		};
		add_filter( 'woocommerce_shipping_methods', $register );
		$instance_id = ( new \WC_Shipping_Zone( 0 ) )->add_shipping_method( 'test_carrier' );
		update_option( "woocommerce_test_carrier_{$instance_id}_settings", array( 'cost' => 'canary-cost' ) );

		$result = $this->run_ability( 'woocommerce/shipping-zones-query', array( 'include' => array( 0 ) ) );
		remove_filter( 'woocommerce_shipping_methods', $register );

		$this->assertSame( 'test_carrier', $result['zones'][0]['methods'][0]['method_id'] );
		$this->assertArrayNotHasKey( 'settings', $result['zones'][0]['methods'][0] );
		$this->assertStringNotContainsString( 'canary', (string) wp_json_encode( $result ) );
	}

	/**
	 * @testdox Should list tax rates of every class with their flags, and filter them by country and by class.
	 */
	public function test_tax_rates_filter_by_country(): void {
		\WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'DE',
				'tax_rate'          => '19.0000',
				'tax_rate_name'     => 'MwSt',
				'tax_rate_class'    => '',
				'tax_rate_compound' => 0,
				'tax_rate_shipping' => 1,
			)
		);
		$reduced = \WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'DE',
				'tax_rate'          => '7.0000',
				'tax_rate_name'     => 'MwSt reduced',
				'tax_rate_class'    => 'reduced-rate',
				'tax_rate_compound' => 1,
				'tax_rate_shipping' => 0,
			)
		);
		\WC_Tax::_update_tax_rate_postcodes( $reduced, '10115; 10117' );
		\WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country' => 'FR',
				'tax_rate'         => '20.0000',
				'tax_rate_name'    => 'TVA',
				'tax_rate_class'   => '',
			)
		);

		$result = $this->run_ability( 'woocommerce/tax-rates-query', array( 'country' => 'de' ) );

		$this->assertNotWPError( $result );
		$this->assertSame( array( 'MwSt', 'MwSt reduced' ), array_column( $result['rates'], 'name' ) );
		$this->assertFalse( $result['rates'][0]['compound'] );
		$this->assertTrue( $result['rates'][0]['shipping'] );
		$this->assertTrue( $result['rates'][1]['compound'] );
		$this->assertFalse( $result['rates'][1]['shipping'] );
		$this->assertSame( 'reduced-rate', $result['rates'][1]['class'] );
		$this->assertSame( array( '10115', '10117' ), $result['rates'][1]['postcodes'] );

		$reduced_only = $this->run_ability( 'woocommerce/tax-rates-query', array( 'class' => 'reduced-rate' ) );

		$this->assertSame( array( $reduced ), array_column( $reduced_only['rates'], 'id' ) );
		$this->assertSame( 1, $reduced_only['total'] );
	}

	/**
	 * @testdox Should refuse shipping and tax reads to a user who cannot manage WooCommerce.
	 */
	public function test_requires_settings_permission(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );

		$this->assertWPError( $this->run_ability( 'woocommerce/shipping-zones-query' ) );
		$this->assertWPError( $this->run_ability( 'woocommerce/tax-rates-query' ) );
	}

	/**
	 * @testdox Should let a shop manager read shipping zones and tax rates.
	 */
	public function test_shop_manager_can_read(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'shop_manager' ) ) );

		$this->assertNotWPError( $this->run_ability( 'woocommerce/shipping-zones-query' ) );
		$this->assertNotWPError( $this->run_ability( 'woocommerce/tax-rates-query' ) );
	}

	/**
	 * @testdox Should page tax rates in the order of the tax settings, and return none for an unknown class.
	 */
	public function test_tax_rates_are_paged(): void {
		$ids = array();
		foreach ( array( 2, 0, 1 ) as $order ) {
			$ids[ $order ] = \WC_Tax::_insert_tax_rate(
				array(
					'tax_rate_country' => 'DE',
					'tax_rate'         => '19.0000',
					'tax_rate_order'   => $order,
				)
			);
		}

		$first   = $this->run_ability( 'woocommerce/tax-rates-query', array( 'per_page' => 2 ) );
		$second  = $this->run_ability(
			'woocommerce/tax-rates-query',
			array(
				'per_page' => 2,
				'page'     => 2,
			)
		);
		$unknown = $this->run_ability( 'woocommerce/tax-rates-query', array( 'class' => 'no-such-class' ) );

		$this->assertSame( array( $ids[0], $ids[1] ), array_column( $first['rates'], 'id' ) );
		$this->assertSame( array( $ids[2] ), array_column( $second['rates'], 'id' ) );
		$this->assertSame( 3, $first['total'] );
		$this->assertSame( 2, $first['total_pages'] );
		$this->assertSame( array(), $unknown['rates'] );
		$this->assertSame( 0, $unknown['total'] );
	}

	/**
	 * @testdox Should list standard rates first, then each class in the order of the tax settings, not by slug.
	 */
	public function test_tax_rates_follow_the_tax_class_order(): void {
		\WC_Tax::create_tax_class( 'Alpha rate', 'zz-alpha-rate' );
		$ids = array();
		foreach ( array( 'zz-alpha-rate', 'reduced-rate', '' ) as $tax_class ) {
			$ids[ $tax_class ] = \WC_Tax::_insert_tax_rate(
				array(
					'tax_rate_country' => 'DE',
					'tax_rate'         => '7.0000',
					'tax_rate_class'   => $tax_class,
				)
			);
		}

		try {
			$result = $this->run_ability( 'woocommerce/tax-rates-query' );
		} finally {
			// The class list is cached, and the cache outlives the test's database rollback.
			\WC_Tax::delete_tax_class_by( 'slug', 'zz-alpha-rate' );
		}

		$this->assertSame( array( $ids[''], $ids['zz-alpha-rate'], $ids['reduced-rate'] ), array_column( $result['rates'], 'id' ) );
	}

	/**
	 * @testdox Should return only the included tax rates, in the order of the tax settings.
	 */
	public function test_tax_rates_filter_by_include(): void {
		$ids = array();
		foreach ( array( 0, 1, 2 ) as $order ) {
			$ids[ $order ] = \WC_Tax::_insert_tax_rate(
				array(
					'tax_rate_country' => 'DE',
					'tax_rate'         => '19.0000',
					'tax_rate_order'   => $order,
				)
			);
		}

		$result = $this->run_ability( 'woocommerce/tax-rates-query', array( 'include' => array( $ids[2], $ids[0] ) ) );

		$this->assertNotWPError( $result );
		$this->assertSame( array( $ids[0], $ids[2] ), array_column( $result['rates'], 'id' ) );
		$this->assertSame( 2, $result['total'] );
	}

	/**
	 * @testdox Should load the postcodes and cities of a whole page in one query, whatever the number of rates.
	 */
	public function test_tax_rate_query_count_does_not_grow_with_the_rates(): void {
		global $wpdb;

		$this->insert_rate_with_locations( 0 );
		$this->run_ability( 'woocommerce/tax-rates-query' );
		$before = $wpdb->num_queries;
		$this->run_ability( 'woocommerce/tax-rates-query' );
		$one_rate = $wpdb->num_queries - $before;

		for ( $i = 1; $i < 5; $i++ ) {
			$this->insert_rate_with_locations( $i );
		}
		$before = $wpdb->num_queries;
		$result = $this->run_ability( 'woocommerce/tax-rates-query' );

		$this->assertCount( 5, $result['rates'] );
		$this->assertSame( $one_rate, $wpdb->num_queries - $before );
	}

	/**
	 * Insert a tax rate with one postcode and one city.
	 *
	 * @param int $i Number that makes the postcode and city unique.
	 */
	private function insert_rate_with_locations( int $i ): void {
		$id = \WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country' => 'US',
				'tax_rate'         => '5.0000',
			)
		);
		\WC_Tax::_update_tax_rate_postcodes( $id, "1000{$i}" );
		\WC_Tax::_update_tax_rate_cities( $id, "City {$i}" );
	}
}
