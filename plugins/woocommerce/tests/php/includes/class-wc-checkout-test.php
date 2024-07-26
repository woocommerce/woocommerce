<?php
/**
 * Unit tests for the WC_Cart_Test class.
 *
 * @package WooCommerce\Tests\Checkout.
 */

use Automattic\WooCommerce\Testing\Tools\CodeHacking\Hacks\FunctionsMockerHack;

/**
 * Class WC_Checkout
 */
class WC_Checkout_Test extends \WC_Unit_Test_Case {

	/**
	 * @var object The system under test.
	 */
	private $sut;

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		// phpcs:disable Generic.CodeAnalysis, Squiz.Commenting
		$this->sut = new class() extends WC_Checkout {
			public function validate_posted_data( &$data, &$errors ) {
				return parent::validate_posted_data( $data, $errors );
			}

			public function validate_checkout( &$data, &$errors ) {
				return parent::validate_checkout( $data, $errors );
			}
		};
		// phpcs:enable Generic.CodeAnalysis, Squiz.Commenting

		WC()->cart->empty_cart();
	}

	/**
	 * @testdox 'validate_posted_data' adds errors for non-existing billing/shipping countries.
	 *
	 * @testWith [true, true]
	 *           [false, false]
	 *
	 * @param bool $ship_to_different_address True to simulate shipping to a different address than the billing address.
	 * @param bool $expect_error_message_for_shipping_country True to expect an error to be generated for the shipping country.
	 */
	public function test_validate_posted_data_adds_error_for_non_existing_country( $ship_to_different_address, $expect_error_message_for_shipping_country ) {
		$_POST = array(
			'billing_country'           => 'XX',
			'shipping_country'          => 'YY',
			'ship_to_different_address' => $ship_to_different_address,
		);
		$data  = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		add_filter(
			'woocommerce_cart_needs_shipping_address',
			function() {
				return true;
			}
		);

		$errors = new WP_Error();

		$this->sut->validate_posted_data( $data, $errors );

		$this->assertEquals( "'XX' is not a valid country code.", $errors->get_error_message( 'billing_country_validation' ) );
		$this->assertEquals(
			$expect_error_message_for_shipping_country ? "'YY' is not a valid country code." : '',
			$errors->get_error_message( 'shipping_country_validation' )
		);
	}

	/**
	 * @testdox the customer notes are correctly sanitized.
	 */
	public function test_order_notes() {
		$_POST = array(
			'ship_to_different_address' => false,
			'order_comments'             => '<a href="http://attackerpage.com/csrf.html">This text should not save inside an anchor.</a><script>alert("alert")</script>',
			'payment_method'            => 'bacs',
		);
		$data  = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		$errors = new WP_Error();

		$this->sut->validate_posted_data( $data, $errors );
		$result = $this->sut->create_order( $data );

		$content = wc_get_template_html(
			'order/order-details.php',
			array(
				'order_id'       => $result,
				'show_downloads' => false,
			)
		);
		$this->assertStringNotContainsString( '<a href="http://attackerpage.com/csrf.html">', $content );
		$this->assertStringNotContainsString( '<script>', $content );
		$this->assertStringContainsString( 'This text should not save inside an anchor.', $content );
	}

	/**
	 * @testdox 'validate_posted_data' doesn't add errors for existing billing/shipping countries.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $ship_to_different_address True to simulate shipping to a different address than the billing address.
	 */
	public function test_validate_posted_data_does_not_add_error_for_existing_country( $ship_to_different_address ) {
		$_POST = array(
			'billing_country'           => 'ES',
			'shipping_country'          => 'ES',
			'ship_to_different_address' => $ship_to_different_address,
		);
		$data  = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		$errors = new WP_Error();

		$this->sut->validate_posted_data( $data, $errors );

		$this->assertEmpty( $errors->get_error_message( 'billing_country_validation' ) );
		$this->assertEmpty( $errors->get_error_message( 'shipping_country_validation' ) );
	}

	/**
	 * @testdox 'validate_posted_data' doesn't add errors for empty billing/shipping countries.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $ship_to_different_address True to simulate shipping to a different address than the billing address.
	 */
	public function test_validate_posted_data_does_not_add_error_for_empty_country( $ship_to_different_address ) {
		$_POST = array(
			'billing_country'           => '',
			'shipping_country'          => '',
			'ship_to_different_address' => $ship_to_different_address,
		);
		$data  = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		$errors = new WP_Error();

		$this->sut->validate_posted_data( $data, $errors );

		$this->assertEmpty( $errors->get_error_message( 'billing_country_validation' ) );
		$this->assertEmpty( $errors->get_error_message( 'shipping_country_validation' ) );
	}

	/**
	 * @testdox 'validate_checkout' adds a "We don't ship to country X" error but only if the country exists.
	 *
	 * @testWith [ "XX", false ]
	 *           [ "JP", true ]
	 *
	 * @param string $country The billing/shipping country.
	 * @param bool   $expect_we_dont_ship_error True to expect a "We don't ship to X" error.
	 */
	public function test_validate_checkout_adds_we_dont_ship_error_only_if_country_exists( $country, $expect_we_dont_ship_error ) {
		add_filter(
			'woocommerce_countries_allowed_countries',
			function() {
				return array( 'ES' );
			}
		);

		add_filter(
			'woocommerce_cart_needs_shipping',
			function() {
				return true;
			}
		);

		add_filter(
			'wc_shipping_enabled',
			function() {
				return true;
			}
		);

		FunctionsMockerHack::add_function_mocks(
			array(
				'wc_get_shipping_method_count' => function( $include_legacy = false, $enabled_only = false ) {
					return 1;
				},
			)
		);

		$_POST = array(
			'billing_country'           => $country,
			'shipping_country'          => $country,
			'ship_to_different_address' => false,
		);
		$data  = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		$errors = new WP_Error();

		$this->sut->validate_checkout( $data, $errors );

		$this->assertEquals(
			$expect_we_dont_ship_error ? 'Unfortunately <strong>we do not ship to the JP</strong>. Please enter an alternative shipping address.' : '',
			$errors->get_error_message( 'shipping' )
		);
	}
}

