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
}
