<?php
/**
 * ShippingAndTaxAbilitiesTest class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\ExtensibleAbilities;
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
	 * Start each test from empty registries, so the abilities build their schemas again.
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
	 * Turn the extensible abilities feature on and register a `test-ext.code` field on a resource.
	 *
	 * @param string   $resource_name Resource.
	 * @param callable $get_callback  Field get_callback.
	 */
	private function add_test_field( string $resource_name, callable $get_callback ): void {
		update_option( 'woocommerce_feature_' . ExtensibleAbilities::FEATURE_ID . '_enabled', 'yes' );
		add_action(
			'woocommerce_extensible_abilities_init',
			static function () use ( $resource_name, $get_callback ) {
				ExtensibleAbilities::register_field(
					array(
						'resource'      => $resource_name,
						'namespace'     => 'test-ext',
						'field'         => 'code',
						'output_schema' => array( 'type' => array( 'string', 'null' ) ),
						'get_callback'  => $get_callback,
					)
				);
			}
		);
	}

	/**
	 * Drop every registered resource, field and ability, so the next read fires the init actions again.
	 */
	private static function reset_registries(): void {
		global $wp_actions;

		foreach ( array( 'resources', 'fields', 'reported', 'validators', 'filters' ) as $property ) {
			$reflection = new \ReflectionProperty( ExtensibleAbilities::class, $property );
			$reflection->setAccessible( true );
			$reflection->setValue( null, array() );
		}

		foreach ( array( 'WP_Abilities_Registry', 'WP_Ability_Categories_Registry' ) as $registry ) {
			if ( class_exists( $registry ) ) {
				$instance = new \ReflectionProperty( $registry, 'instance' );
				$instance->setAccessible( true );
				$instance->setValue( null, null );
			}
		}
		unset( $wp_actions['wp_abilities_api_init'], $wp_actions['wp_abilities_api_categories_init'], $wp_actions['woocommerce_extensible_abilities_resources_init'], $wp_actions['woocommerce_extensible_abilities_init'] );
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
	 * @testdox Should add extension fields to each zone while the feature is on.
	 */
	public function test_zones_take_extension_fields(): void {
		$this->add_test_field(
			'shipping_zone',
			static function ( \WC_Shipping_Zone $zone ) {
				return 'zone-' . $zone->get_id();
			}
		);

		$result = $this->run_ability( 'woocommerce/shipping-zones-query', array( 'include' => array( 0 ) ) );

		$this->assertSame( 'zone-0', $result['zones'][0]['extensions']['test-ext']['code'] );
	}

	/**
	 * @testdox Should add extension fields to each shipping method while the feature is on.
	 */
	public function test_methods_take_extension_fields(): void {
		$instance_id = ( new \WC_Shipping_Zone( 0 ) )->add_shipping_method( 'flat_rate' );
		$this->add_test_field(
			'shipping_method',
			static function ( \WC_Shipping_Method $method ) {
				return 'method-' . $method->get_instance_id();
			}
		);

		$result = $this->run_ability( 'woocommerce/shipping-zones-query', array( 'include' => array( 0 ) ) );

		$this->assertSame( "method-{$instance_id}", $result['zones'][0]['methods'][0]['extensions']['test-ext']['code'] );
	}

	/**
	 * @testdox Should leave secret settings out of a method, also those declared through the form fields filter.
	 */
	public function test_method_settings_leave_out_secrets(): void {
		add_filter(
			'woocommerce_shipping_instance_form_fields_flat_rate',
			static function ( array $fields ): array {
				$fields['carrier_login']   = array(
					'title' => 'Carrier login',
					'type'  => 'password',
				);
				$fields['carrier_api_key'] = array(
					'title' => 'Carrier API key',
					'type'  => 'text',
				);
				$fields['carrier_account'] = array(
					'title' => 'Carrier account',
					'type'  => 'text',
				);
				return $fields;
			}
		);
		$zone        = new \WC_Shipping_Zone( 0 );
		$instance_id = $zone->add_shipping_method( 'flat_rate' );
		update_option(
			"woocommerce_flat_rate_{$instance_id}_settings",
			array(
				'cost'            => '4.50',
				'carrier_login'   => 'canary-login',
				'carrier_api_key' => 'canary-key',
				'carrier_account' => 'ACME-1',
				'undeclared'      => 'stale',
			)
		);

		$result   = $this->run_ability( 'woocommerce/shipping-zones-query', array( 'include' => array( 0 ) ) );
		$settings = (array) $result['zones'][0]['methods'][0]['settings'];

		$this->assertSame( '4.50', $settings['cost'] );
		$this->assertSame( 'ACME-1', $settings['carrier_account'] );
		$this->assertArrayNotHasKey( 'carrier_login', $settings );
		$this->assertArrayNotHasKey( 'carrier_api_key', $settings );
		$this->assertArrayNotHasKey( 'undeclared', $settings );
		$this->assertStringNotContainsString( 'canary', (string) wp_json_encode( $result ) );
	}

	/**
	 * @testdox Should return zones and methods without extensions while the feature is off.
	 */
	public function test_zones_and_methods_have_no_extensions_with_the_feature_off(): void {
		( new \WC_Shipping_Zone( 0 ) )->add_shipping_method( 'flat_rate' );

		$result = $this->run_ability( 'woocommerce/shipping-zones-query', array( 'include' => array( 0 ) ) );

		$this->assertArrayNotHasKey( 'extensions', $result['zones'][0] );
		$this->assertArrayNotHasKey( 'extensions', $result['zones'][0]['methods'][0] );
	}

	/**
	 * @testdox Should list tax rates of every class and filter them by country.
	 */
	public function test_tax_rates_filter_by_country(): void {
		\WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country' => 'DE',
				'tax_rate'         => '19.0000',
				'tax_rate_name'    => 'MwSt',
				'tax_rate_class'   => '',
			)
		);
		$reduced = \WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country' => 'DE',
				'tax_rate'         => '7.0000',
				'tax_rate_name'    => 'MwSt reduced',
				'tax_rate_class'   => 'reduced-rate',
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
		$this->assertSame( 'reduced-rate', $result['rates'][1]['class'] );
		$this->assertSame( array( '10115', '10117' ), $result['rates'][1]['postcodes'] );
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
