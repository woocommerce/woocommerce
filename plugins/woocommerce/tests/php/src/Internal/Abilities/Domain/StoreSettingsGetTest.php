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
				'completed'   => true,
				'store_email' => 'private@example.test',
				'api_key'     => 'secret-sentinel',
				'industry'    => array( array( 'password' => 'secret-sentinel' ) ),
			)
		);
		$result = $this->sut->execute();
		$this->assertIsArray( $result );
		$this->assertTrue( $result['onboarding_profile']->completed );
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
