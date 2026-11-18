<?php
/**
 * Store settings ability tests.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities\Domain;

use Automattic\WooCommerce\Internal\Abilities\AbilitiesLoader;

/**
 * Tests the registered store configuration read.
 */
class StoreSettingsGetTest extends \WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var \WP_Ability
	 */
	private $sut;

	/**
	 * Register abilities and authenticate a store manager.
	 */
	public function setUp(): void {
		parent::setUp();
		add_action( 'wp_abilities_api_init', array( AbilitiesLoader::class, 'register_abilities' ) );
		do_action( 'wp_abilities_api_init' );
		$this->sut = wp_get_ability( 'woocommerce/store-settings-get' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'shop_manager' ) ) );
	}

	/**
	 * @testdox Should read current configuration without requiring input.
	 */
	public function test_reads_configuration_without_input(): void {
		update_option( 'woocommerce_default_country', 'US:CA' );
		update_option( 'woocommerce_currency', 'EUR' );
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_admin_install_timestamp', 1609459200 );
		$result = $this->sut->execute();
		$this->assertIsArray( $result );
		$this->assertSame( 'US', $result['address']['country_code'] );
		$this->assertSame( 'CA', $result['address']['state_code'] );
		$this->assertSame( 'EUR', $result['currency']['code'] );
		$this->assertSame( '€', $result['currency']['symbol'] );
		$this->assertTrue( $result['tax']['prices_include_tax'] );
		$this->assertSame( 1609459200, $result['installed_at'] );
	}

	/**
	 * @testdox Should omit summary fields when their source is not readable.
	 * @testWith ["general", "woocommerce_default_country", "address", ["country_code", "state_code"]]
	 *           ["general", "woocommerce_currency_pos", "currency", ["symbol_position", "price_format"]]
	 *           ["products", "woocommerce_weight_unit", "units", ["weight"]]
	 *           ["tax", "woocommerce_tax_display_cart", "tax", ["display_in_cart"]]
	 *           ["account", "woocommerce_enable_guest_checkout", "capabilities", ["guest_checkout"]]
	 * @param string $group Source group.
	 * @param string $id Source setting.
	 * @param string $section Summary section.
	 * @param array  $fields Dependent fields.
	 */
	public function test_summary_opt_out( string $group, string $id, string $section, array $fields ): void {
		$before = $this->sut->execute();
		foreach ( $fields as $field ) {
			$this->assertArrayHasKey( $field, $before[ $section ] );
		}
		add_filter(
			'woocommerce_settings-' . $group,
			static function ( $settings ) use ( $id ) {
				foreach ( $settings as &$setting ) {
					if ( $id === $setting['id'] ) {
						$setting['abilities']['read'] = false;
					}
				}
				return $settings;
			},
			100
		);
		$result = $this->sut->execute();
		$this->assertIsArray( $result );
		foreach ( $fields as $field ) {
			$this->assertArrayNotHasKey( $field, $result[ $section ] );
		}
		$this->assertSame( $before['timezone'], $result['timezone'] );
		$this->assertSame( WC_VERSION, $result['woocommerce_version'] );
	}

	/**
	 * @testdox Should combine only readable address lines and retain empty object schemas.
	 * @testWith [["woocommerce_store_address"], "Suite 2"]
	 *           [["woocommerce_store_address_2"], "Main Street"]
	 *           [["woocommerce_store_address", "woocommerce_store_address_2"], null]
	 * @param array       $denied Hidden setting IDs.
	 * @param string|null $street Expected street.
	 */
	public function test_partial_address( array $denied, ?string $street ): void {
		update_option( 'woocommerce_store_address', 'Main Street' );
		update_option( 'woocommerce_store_address_2', 'Suite 2' );
		add_filter(
			'woocommerce_settings-general',
			static function ( $settings ) use ( $denied ) {
				return array_filter( $settings, static fn( $setting ) => ! in_array( $setting['id'], $denied, true ) );
			},
			100
		);
		$result = $this->sut->execute();
		if ( null === $street ) {
			$this->assertArrayNotHasKey( 'street', $result['address'] );
		} else {
			$this->assertSame( $street, $result['address']['street'] );
		}
		add_filter( 'woocommerce_settings-general', '__return_empty_array', 101 );
		$result = $this->sut->execute();
		$this->assertInstanceOf( \stdClass::class, $result['address'] );
		$this->assertSame( array(), (array) $result['address'] );
		$this->assertArrayNotHasKey( 'selling_locations', $result );
	}

	/**
	 * @testdox Should omit a country list independently of its readable scope and reject password opt-ins.
	 */
	public function test_country_list_opt_out(): void {
		update_option( 'woocommerce_allowed_countries', 'specific' );
		update_option( 'woocommerce_specific_allowed_countries', array( 'GB' ) );
		add_filter(
			'woocommerce_settings-general',
			static function ( $settings ) {
				foreach ( $settings as &$setting ) {
					if ( 'woocommerce_specific_allowed_countries' === $setting['id'] ) {
						$setting['type'] = 'password';
					}
				}
				return $settings;
			},
			100
		);
		$result = $this->sut->execute();
		$this->assertSame( array( 'scope' => 'specific' ), $result['selling_locations'] );
	}

	/**
	 * @testdox Should honor a truthy integer returned by the REST permission filter.
	 */
	public function test_integer_permission_filter(): void {
		add_filter( 'woocommerce_rest_check_permissions', static fn() => 1 );
		$this->assertIsArray( $this->sut->execute() );
	}

	/**
	 * @testdox Should normalize accepted onboarding values to their declared types.
	 * @testWith ["false", false]
	 *           ["true", true]
	 *           [false, false]
	 *           [true, true]
	 * @param mixed $stored Stored boolean value.
	 * @param bool  $expected Expected boolean value.
	 */
	public function test_onboarding_boolean_values( $stored, bool $expected ): void {
		update_option(
			'woocommerce_onboarding_profile',
			array(
				'completed' => $stored,
				'industry'  => array( 'fashion' ),
			)
		);
		$result = $this->sut->execute();
		$this->assertSame( $expected, $result['onboarding_profile']->completed );
		$this->assertSame( array( 'fashion' ), $result['onboarding_profile']->industry );
	}

	/**
	 * @testdox Should expose only the country lists used by the active location settings.
	 */
	public function test_location_scopes(): void {
		update_option( 'woocommerce_allowed_countries', 'all_except' );
		update_option( 'woocommerce_all_except_countries', array( 'US' ) );
		update_option( 'woocommerce_specific_allowed_countries', array( 'GB' ) );
		update_option( 'woocommerce_ship_to_countries', '' );
		$result = $this->sut->execute();
		$this->assertSame( array( 'US' ), $result['selling_locations']['excluded_countries'] );
		$this->assertArrayNotHasKey( 'included_countries', $result['selling_locations'] );
		$this->assertSame( 'same_as_selling_locations', $result['shipping_locations']['scope'] );
		update_option( 'woocommerce_ship_to_countries', 'specific' );
		update_option( 'woocommerce_specific_ship_to_countries', array( 'CA' ) );
		$this->assertSame( array( 'CA' ), $this->sut->execute()['shipping_locations']['included_countries'] );
	}

	/**
	 * @testdox Should omit onboarding email and unrecognized fields, including nested secrets.
	 */
	public function test_onboarding_secrets_are_excluded(): void {
		update_option(
			'woocommerce_onboarding_profile',
			array(
				'completed'       => true,
				'business_choice' => null,
				'store_email'     => 'private@example.test',
				'api_key'         => 'secret-sentinel',
				'industry'        => array( array( 'password' => 'secret-sentinel' ) ),
			)
		);
		$result = $this->sut->execute();
		$this->assertIsArray( $result );
		$this->assertTrue( $result['onboarding_profile']->completed );
		$this->assertArrayNotHasKey( 'business_choice', (array) $result['onboarding_profile'] );
		$this->assertStringNotContainsString( 'secret-sentinel', wp_json_encode( $result ) );
		$this->assertStringNotContainsString( 'private@example.test', wp_json_encode( $result ) );
	}

	/**
	 * @testdox Should serialize an empty onboarding profile as a schema-valid object.
	 */
	public function test_empty_onboarding_profile_shape(): void {
		delete_option( 'woocommerce_onboarding_profile' );
		$result = json_decode( wp_json_encode( $this->sut->execute() ) );
		$this->assertInstanceOf( \stdClass::class, $result->onboarding_profile );
	}

	/**
	 * @testdox Should refuse callers without store management permission.
	 */
	public function test_permission_denied(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'customer' ) ) );
		$this->assertWPError( $this->sut->execute() );
	}
}
