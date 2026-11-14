<?php
/**
 * ShippingWritesTest class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities\Domain;

use Automattic\WooCommerce\Internal\Abilities\AbilitiesLoader;

/**
 * Tests for the shipping-zone-create, shipping-zone-method-create and shipping-zone-method-update abilities.
 */
class ShippingWritesTest extends \WC_Unit_Test_Case {

	private const GERMANY = array(
		array(
			'code' => 'DE',
			'type' => 'country',
		),
	);

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
		global $wp_actions;

		if ( ! function_exists( 'wp_register_ability' ) ) {
			require_once WC_ABSPATH . 'vendor/wordpress/abilities-api/includes/bootstrap.php';
		}

		// The Abilities API registry refuses to initialize before `init` has fired.
		$this->original_init_count = $wp_actions['init'] ?? null;
		$wp_actions['init']        = max( 1, (int) ( $wp_actions['init'] ?? 0 ) ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		parent::setUp();

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
	 * Create a zone for Germany through the ability.
	 *
	 * @return int Zone ID.
	 */
	private function create_germany_zone(): int {
		$result = $this->run_ability(
			'woocommerce/shipping-zone-create',
			array(
				'name'      => 'Germany',
				'locations' => array(
					array(
						'code' => 'DE',
						'type' => 'country',
					),
				),
			)
		);
		$this->assertNotWPError( $result );

		return $result['zone']['id'];
	}

	/**
	 * Put one product at the price in a fresh cart shipping to Germany, and return the offered rates.
	 *
	 * @param float $price Product price.
	 * @return array<string, string> Cost by method ID.
	 */
	private function rates_for_cart_of( float $price ): array {
		WC()->cart->empty_cart();
		WC()->customer->set_shipping_country( 'DE' );
		WC()->customer->set_billing_country( 'DE' );

		$product = new \WC_Product_Simple();
		$product->set_regular_price( (string) $price );
		$product->save();
		$this->assertNotFalse( WC()->cart->add_to_cart( $product->get_id(), 1 ), 'The fixture product should reach the cart.' );
		WC()->cart->calculate_totals();

		$rates = array();
		foreach ( WC()->shipping()->get_packages()[0]['rates'] ?? array() as $rate ) {
			$rates[ $rate->get_method_id() ] = $rate->get_cost();
		}
		ksort( $rates );

		return $rates;
	}

	/**
	 * @testdox Should let an agent set up a zone with a flat rate and free shipping over a minimum.
	 */
	public function test_sets_up_a_zone_with_flat_rate_and_free_shipping_over_a_minimum(): void {
		$zone_id = $this->create_germany_zone();

		$flat_rate = $this->run_ability(
			'woocommerce/shipping-zone-method-create',
			array(
				'zone_id'   => $zone_id,
				'method_id' => 'flat_rate',
				'settings'  => array( 'cost' => '5' ),
			)
		);
		$free      = $this->run_ability(
			'woocommerce/shipping-zone-method-create',
			array(
				'zone_id'   => $zone_id,
				'method_id' => 'free_shipping',
				'settings'  => array( 'min_amount' => '50' ),
			)
		);

		$this->assertNotWPError( $flat_rate );
		$this->assertNotWPError( $free );
		$this->assertSame( 'min_amount', ( (array) $free['method']['settings'] )['requires'], 'A minimum sent alone should become the free shipping requirement.' );
		$this->assertSame( array( 'flat_rate' => '5' ), $this->rates_for_cart_of( 40 ), 'Below the minimum only the flat rate applies.' );
		$this->assertSame(
			array(
				'flat_rate'     => '5',
				'free_shipping' => '0',
			),
			$this->rates_for_cart_of( 60 ),
			'Above the minimum free shipping is offered too.'
		);

		$zones = $this->run_ability( 'woocommerce/shipping-zones-query', array( 'include' => array( $zone_id ) ) );
		$this->assertSame( array( 'flat_rate', 'free_shipping' ), array_column( $zones['zones'][0]['methods'], 'method_id' ) );
	}

	/**
	 * @testdox Should charge the new cost in a cart whose rates were already calculated.
	 */
	public function test_cost_change_reaches_a_cart_with_calculated_rates(): void {
		$zone_id = $this->create_germany_zone();
		$created = $this->run_ability(
			'woocommerce/shipping-zone-method-create',
			array(
				'zone_id'   => $zone_id,
				'method_id' => 'flat_rate',
				'settings'  => array( 'cost' => '5' ),
			)
		);
		$this->assertSame( array( 'flat_rate' => '5' ), $this->rates_for_cart_of( 40 ) );

		$updated = $this->run_ability(
			'woocommerce/shipping-zone-method-update',
			array(
				'instance_id' => $created['method']['instance_id'],
				'settings'    => array( 'cost' => '10 + [qty] * 2' ),
			)
		);

		$this->assertNotWPError( $updated );
		$this->assertSame( '10 + [qty] * 2', ( (array) $updated['method']['settings'] )['cost'] );
		WC()->cart->calculate_totals();
		$this->assertSame( '12', WC()->shipping()->get_packages()[0]['rates'][ 'flat_rate:' . $created['method']['instance_id'] ]->get_cost() );
	}

	/**
	 * @testdox Should refresh cart rates when the settings are saved but the enabled state is not.
	 */
	public function test_saved_settings_reach_the_cart_when_the_rest_of_the_write_fails(): void {
		global $wpdb;

		$zone_id = $this->create_germany_zone();
		$created = $this->run_ability(
			'woocommerce/shipping-zone-method-create',
			array(
				'zone_id'   => $zone_id,
				'method_id' => 'flat_rate',
				'settings'  => array( 'cost' => '5' ),
			)
		);
		$this->rates_for_cart_of( 40 );

		$break_method_update = static function ( string $query ) use ( $wpdb ): string {
			return 0 === strpos( $query, "UPDATE `{$wpdb->prefix}woocommerce_shipping_zone_methods`" ) ? 'UPDATE no_such_table SET x = 1' : $query;
		};
		add_filter( 'query', $break_method_update );
		$suppress = $wpdb->suppress_errors( true );
		$updated  = $this->run_ability(
			'woocommerce/shipping-zone-method-update',
			array(
				'instance_id' => $created['method']['instance_id'],
				'enabled'     => true,
				'settings'    => array( 'cost' => '7' ),
			)
		);
		$wpdb->suppress_errors( $suppress );
		remove_filter( 'query', $break_method_update );

		$this->assertWPError( $updated );
		$this->assertSame( 'woocommerce_ability_shipping_method_update_failed', $updated->get_error_code() );
		WC()->cart->calculate_totals();
		$this->assertSame( '7', WC()->shipping()->get_packages()[0]['rates'][ 'flat_rate:' . $created['method']['instance_id'] ]->get_cost() );
	}

	/**
	 * @testdox Should keep the free shipping requirement already stored when only the minimum changes.
	 */
	public function test_minimum_keeps_a_stored_requirement(): void {
		$created = $this->run_ability(
			'woocommerce/shipping-zone-method-create',
			array(
				'zone_id'   => 0,
				'method_id' => 'free_shipping',
				'settings'  => array( 'requires' => 'coupon' ),
			)
		);

		$updated = $this->run_ability(
			'woocommerce/shipping-zone-method-update',
			array(
				'instance_id' => $created['method']['instance_id'],
				'settings'    => array( 'min_amount' => '75' ),
			)
		);

		$settings = (array) $updated['method']['settings'];
		$this->assertSame( 'coupon', $settings['requires'] );
		$this->assertSame( '75', $settings['min_amount'] );
	}

	/**
	 * @testdox Should store free text as sent and the coupon rule checkbox as yes or no.
	 */
	public function test_writes_text_and_checkbox_settings(): void {
		$created = $this->run_ability(
			'woocommerce/shipping-zone-method-create',
			array(
				'zone_id'   => 0,
				'method_id' => 'free_shipping',
				'settings'  => array(
					'title'            => 'Free \\ fast',
					'ignore_discounts' => true,
				),
			)
		);
		$this->assertSame( 'Free \\ fast', $created['method']['title'] );
		$this->assertSame( 'yes', ( (array) $created['method']['settings'] )['ignore_discounts'] );

		$updated = $this->run_ability(
			'woocommerce/shipping-zone-method-update',
			array(
				'instance_id' => $created['method']['instance_id'],
				'enabled'     => false,
				'settings'    => array( 'ignore_discounts' => false ),
			)
		);

		$this->assertFalse( $updated['method']['enabled'] );
		$this->assertSame( 'no', ( (array) $updated['method']['settings'] )['ignore_discounts'] );
	}

	/**
	 * @testdox Should refuse settings the method rejects, and leave no method behind.
	 * @testWith ["flat_rate", {"min_amount": "50"}, "woocommerce_ability_shipping_method_setting_unknown"]
	 *           ["flat_rate", {"cost": "10 +"}, "woocommerce_ability_shipping_method_setting_invalid"]
	 *           ["free_shipping", {"min_amount": "-5"}, "woocommerce_ability_shipping_method_setting_invalid"]
	 *           ["free_shipping", {"min_amount": ","}, "woocommerce_ability_shipping_method_setting_invalid"]
	 *           ["free_shipping", {"min_amount": ""}, "woocommerce_ability_shipping_method_setting_invalid"]
	 *
	 * @param string $method_id Method to add.
	 * @param array  $settings  Settings to send.
	 * @param string $code      Expected error code.
	 */
	public function test_refuses_bad_settings_without_adding_the_method( string $method_id, array $settings, string $code ): void {
		$zone_id = $this->create_germany_zone();

		$result = $this->run_ability(
			'woocommerce/shipping-zone-method-create',
			array(
				'zone_id'   => $zone_id,
				'method_id' => $method_id,
				'settings'  => $settings,
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( $code, $result->get_error_code() );
		$this->assertSame( array(), ( new \WC_Shipping_Zone( $zone_id ) )->get_shipping_methods(), 'The method should be removed again.' );
	}

	/**
	 * @testdox Should remove the method again when it cannot be read back after adding it.
	 */
	public function test_removes_a_method_it_cannot_read_back(): void {
		$zone_id    = $this->create_germany_zone();
		$unregister = static function ( array $methods ): array {
			unset( $methods['flat_rate'] );
			return $methods;
		};
		add_action(
			'woocommerce_shipping_zone_method_added',
			static function () use ( $unregister ): void {
				add_filter( 'woocommerce_shipping_methods', $unregister );
			}
		);

		$result = $this->run_ability(
			'woocommerce/shipping-zone-method-create',
			array(
				'zone_id'   => $zone_id,
				'method_id' => 'flat_rate',
			)
		);
		remove_filter( 'woocommerce_shipping_methods', $unregister );

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_ability_shipping_method_create_failed', $result->get_error_code() );
		$this->assertSame( array(), ( new \WC_Shipping_Zone( $zone_id ) )->get_shipping_methods( false, 'admin' ), 'The method should be removed again.' );
	}

	/**
	 * @testdox Should refuse a zone or method that does not exist.
	 * @testWith ["woocommerce/shipping-zone-method-create", {"zone_id": 999999, "method_id": "flat_rate"}, "woocommerce_ability_shipping_zone_not_found"]
	 *           ["woocommerce/shipping-zone-method-update", {"instance_id": 999999, "enabled": true}, "woocommerce_ability_shipping_method_not_found"]
	 *
	 * @param string $ability Ability name.
	 * @param array  $input   Ability input.
	 * @param string $code    Expected error code.
	 */
	public function test_refuses_missing_targets( string $ability, array $input, string $code ): void {
		$result = $this->run_ability( $ability, $input );

		$this->assertWPError( $result );
		$this->assertSame( $code, $result->get_error_code() );
	}

	/**
	 * @testdox Should refuse to change a method from an extension, even one that extends a Core method.
	 */
	public function test_refuses_extension_methods(): void {
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

		$result = $this->run_ability(
			'woocommerce/shipping-zone-method-update',
			array(
				'instance_id' => $instance_id,
				'settings'    => array( 'cost' => '1' ),
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_ability_shipping_method_unsupported', $result->get_error_code() );
		$this->assertFalse( get_option( "woocommerce_test_carrier_{$instance_id}_settings" ), 'Nothing should be written for the extension method.' );
	}

	/**
	 * @testdox Should create a zone with state and postcode locations in the given order.
	 */
	public function test_creates_a_zone_with_locations(): void {
		$result = $this->run_ability(
			'woocommerce/shipping-zone-create',
			array(
				'name'      => 'California <b>metro</b>',
				'order'     => 3,
				'locations' => array(
					array(
						'code' => 'US:CA',
						'type' => 'state',
					),
					array(
						'code' => '90*',
						'type' => 'postcode',
					),
				),
			)
		);

		$this->assertNotWPError( $result );
		$this->assertSame( 'California metro', $result['zone']['name'] );
		$this->assertSame( 3, $result['zone']['order'] );
		$this->assertSame(
			array(
				array(
					'code' => 'US:CA',
					'type' => 'state',
				),
				array(
					'code' => '90*',
					'type' => 'postcode',
				),
			),
			$result['zone']['locations']
		);
		$this->assertSame( array(), $result['zone']['methods'] );
	}

	/**
	 * @testdox Should refuse a zone without locations, which would match every address, or with a code longer than the column.
	 * @testWith [[]]
	 *           [[{"code": "%s", "type": "postcode"}]]
	 *
	 * @param array $locations Locations to send.
	 */
	public function test_refuses_zone_locations_that_cannot_be_saved( array $locations ): void {
		$zone_count = count( \WC_Shipping_Zones::get_zones() );
		if ( isset( $locations[0]['code'] ) ) {
			$locations[0]['code'] = str_repeat( '1', 201 );
		}

		$result = $this->run_ability(
			'woocommerce/shipping-zone-create',
			array(
				'name'      => 'Everywhere',
				'locations' => $locations,
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'ability_invalid_input', $result->get_error_code() );
		$this->assertCount( $zone_count, \WC_Shipping_Zones::get_zones(), 'No zone should be created.' );
	}

	/**
	 * @testdox Should refuse a zone name with nothing left after cleaning.
	 */
	public function test_refuses_an_empty_zone_name(): void {
		$result = $this->run_ability(
			'woocommerce/shipping-zone-create',
			array(
				'name'      => '  <b></b> ',
				'locations' => self::GERMANY,
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_ability_shipping_zone_name_empty', $result->get_error_code() );
	}

	/**
	 * @testdox Should refuse every shipping write while shipping is turned off, and save nothing.
	 */
	public function test_refuses_writes_while_shipping_is_disabled(): void {
		$instance_id = ( new \WC_Shipping_Zone( 0 ) )->add_shipping_method( 'flat_rate' );
		$zone_count  = count( \WC_Shipping_Zones::get_zones() );
		update_option( 'woocommerce_ship_to_countries', 'disabled' );

		$results = array(
			$this->run_ability(
				'woocommerce/shipping-zone-create',
				array(
					'name'      => 'Refused',
					'locations' => self::GERMANY,
				)
			),
			$this->run_ability(
				'woocommerce/shipping-zone-method-create',
				array(
					'zone_id'   => 0,
					'method_id' => 'free_shipping',
				)
			),
			$this->run_ability(
				'woocommerce/shipping-zone-method-update',
				array(
					'instance_id' => $instance_id,
					'settings'    => array( 'cost' => '9' ),
				)
			),
		);

		foreach ( $results as $result ) {
			$this->assertWPError( $result );
			$this->assertSame( 'woocommerce_ability_shipping_disabled', $result->get_error_code() );
		}
		$this->assertCount( $zone_count, \WC_Shipping_Zones::get_zones(), 'No zone should be created.' );
		$this->assertCount( 1, ( new \WC_Shipping_Zone( 0 ) )->get_shipping_methods(), 'No method should be added.' );
		$this->assertFalse( get_option( "woocommerce_flat_rate_{$instance_id}_settings" ), 'The flat rate settings should stay untouched.' );
	}

	/**
	 * @testdox Should let a shop manager write shipping, and refuse a user who cannot manage WooCommerce.
	 */
	public function test_requires_settings_permission(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$this->assertWPError(
			$this->run_ability(
				'woocommerce/shipping-zone-create',
				array(
					'name'      => 'Refused',
					'locations' => self::GERMANY,
				)
			)
		);
		$this->assertWPError(
			$this->run_ability(
				'woocommerce/shipping-zone-method-create',
				array(
					'zone_id'   => 0,
					'method_id' => 'flat_rate',
				)
			)
		);
		$this->assertSame( array(), ( new \WC_Shipping_Zone( 0 ) )->get_shipping_methods() );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'shop_manager' ) ) );
		$this->assertGreaterThan( 0, $this->create_germany_zone() );
	}
}
