<?php
namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes\OrderConfirmation;

use Automattic\WooCommerce\StoreApi\Formatters;
use Automattic\WooCommerce\StoreApi\Formatters\CurrencyFormatter;
use Automattic\WooCommerce\StoreApi\Formatters\HtmlFormatter;
use Automattic\WooCommerce\StoreApi\Formatters\MoneyFormatter;
use Automattic\WooCommerce\StoreApi\Routes\V1\Checkout as CheckoutRoute;
use Automattic\WooCommerce\StoreApi\SchemaController;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CheckoutSchema;
use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;
use Automattic\WooCommerce\Tests\Blocks\Mocks\OrderConfirmation\TotalsMock;
use Automattic\WooCommerce\Tests\Blocks\StoreApi\MockSessionHandler;

/**
 * Tests for the Totals block type inside the Order Confirmation.
 *
 * @since $VID:$
 */
class Totals extends \WP_UnitTestCase {
	/**
	 * Setup test product data. Called before every test.
	 */
	protected function setUp(): void {
		parent::setUp();

		global $wp_rest_server;
		$wp_rest_server = new \Spy_REST_Server();
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'rest_api_init', $wp_rest_server );

		wp_set_current_user( 0 );
		$customer = get_user_by( 'email', 'testaccount@test.com' );

		if ( $customer ) {
			wp_delete_user( $customer->ID );
		}

		$formatters = new Formatters();
		$formatters->register( 'money', MoneyFormatter::class );
		$formatters->register( 'html', HtmlFormatter::class );
		$formatters->register( 'currency', CurrencyFormatter::class );

		$this->mock_extend = new ExtendSchema( $formatters );
		$this->mock_extend->register_endpoint_data(
			array(
				'endpoint'        => CheckoutSchema::IDENTIFIER,
				'namespace'       => 'extension_namespace',
				'schema_callback' => function () {
					return array(
						'extension_key' => array(
							'description' => 'Test key',
							'type'        => 'boolean',
						),
					);
				},
			)
		);
		$schema_controller = new SchemaController( $this->mock_extend );
		$route             = new CheckoutRoute( $schema_controller, $schema_controller->get( 'checkout' ) );
		register_rest_route( $route->get_namespace(), $route->get_path(), $route->get_args(), true );

		$fixtures = new FixtureData();

		// Add a flat rate to the default zone.
		$flat_rate    = WC()->shipping()->get_shipping_methods()['flat_rate'];
		$default_zone = \WC_Shipping_Zones::get_zone( 0 );
		$default_zone->add_shipping_method( $flat_rate->id );
		$default_zone->save();

		$fixtures->payments_enable_bacs();
		$this->products = array(
			$fixtures->get_simple_product(
				array(
					'name'          => 'Test Product 1',
					'stock_status'  => 'instock',
					'regular_price' => 10,
					'weight'        => 10,
				)
			),
			$fixtures->get_simple_product(
				array(
					'name'          => 'Test Product 2',
					'stock_status'  => 'instock',
					'regular_price' => 10,
					'weight'        => 10,
				)
			),
		);
		wc_empty_cart();
		wc()->cart->add_to_cart( $this->products[0]->get_id(), 2 );
		wc()->cart->add_to_cart( $this->products[1]->get_id(), 1 );
	}
	/**
	 * We ensure deep sort works with all sort of arrays.
	 */
	public function test_order_notes_cleaned() {
		// We need to replace the WC_Session with a mock because this test relies on cookies being set which
		// is not easy with PHPUnit. This is a simpler approach.
		$old_session  = WC()->session;
		WC()->session = new MockSessionHandler();
		WC()->session->init();

		update_option( 'woocommerce_enable_guest_checkout', 'yes' );
		update_option( 'woocommerce_enable_signup_and_login_from_checkout', 'yes' );

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'billing_address'  => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => 'test',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => 'cb241ab',
					'country'    => 'GB',
					'phone'      => '',
					'email'      => 'testaccount@test.com',
				),
				'shipping_address' => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => 'test',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => 'cb241ab',
					'country'    => 'GB',
					'phone'      => '',
				),
				'create_account'   => true,
				'customer_note'    => '<a href="http://attackerpage.com/csrf.html">This text should not save inside an anchor.</a><script>alert("alert")</script>',
				'payment_method'   => 'bacs',
				'extensions'       => array(
					'extension_namespace' => array(
						'extension_key' => true,
					),
				),
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$status   = $response->get_status();
		$data     = $response->get_data();

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
		$this->assertEquals( $status, 200, print_r( $data, true ) );
		$this->assertTrue( $data['customer_id'] > 0 );

		$order_id    = $data['order_id'];
		$totals_mock = new TotalsMock();
		$content     = $totals_mock->render_content( wc_get_order( $order_id ), true, [], '' );

		// Check the anchor tag is not present in the output but the text inside it is.
		$this->assertStringNotContainsString( '<a href="http://attackerpage.com/csrf.html">', $content );
		$this->assertStringNotContainsString( '<script>', $content );
		$this->assertStringContainsString( 'This text should not save inside an anchor.', $content );

		WC()->session = $old_session;
	}
}

