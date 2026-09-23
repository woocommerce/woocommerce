<?php
/**
 * Tests for the payments API.
 *
 * @package WooCommerce\Tests\Blocks\Payments
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Payments;

use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\Payments\Api;
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;
use WC_Unit_Test_Case;

/**
 * Tests for the payments API.
 */
class ApiTest extends WC_Unit_Test_Case {

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		set_current_screen( 'front' );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		set_current_screen( 'front' );
		parent::tearDown();
	}

	/**
	 * @testdox Should append frontend styles to Cart and Checkout view style handles.
	 *
	 * @dataProvider cart_and_checkout_block_types
	 *
	 * @param string $block_type Block type name.
	 */
	public function test_appends_frontend_styles_to_cart_and_checkout_view_style_handles( $block_type ): void {
		$payment_method_registry = new PaymentMethodRegistry();
		$payment_method_registry->register(
			$this->create_style_payment_method(
				array( 'shared-style', 'payment-method-style' ),
				array( 'payment-method-editor-style' )
			)
		);
		$sut  = $this->get_blocks_payments_api( $payment_method_registry );
		$args = array(
			'style_handles'      => array( 'wc-blocks-style' ),
			'view_style_handles' => array( 'shared-style' ),
		);

		$result = $sut->maybe_add_payment_method_style_handles( $args, $block_type );

		$this->assertSame(
			array( 'shared-style', 'payment-method-style' ),
			$result['view_style_handles'],
			'Payment method view styles should be appended without duplicates.'
		);
		$this->assertSame(
			array( 'wc-blocks-style' ),
			$result['style_handles'],
			'Existing block styles should remain unchanged.'
		);
		$this->assertArrayNotHasKey( 'editor_style_handles', $result );
	}

	/**
	 * @testdox Should append admin styles to editor style handles.
	 */
	public function test_appends_admin_styles_to_editor_style_handles(): void {
		set_current_screen( 'edit-post' );

		$payment_method_registry = new PaymentMethodRegistry();
		$payment_method_registry->register(
			$this->create_style_payment_method(
				array( 'payment-method-style' ),
				array( 'shared-editor-style', 'payment-method-editor-style' )
			)
		);
		$api  = $this->get_blocks_payments_api( $payment_method_registry );
		$args = array(
			'style_handles'        => array( 'wc-blocks-style' ),
			'editor_style_handles' => array( 'shared-editor-style' ),
		);

		$result = $api->maybe_add_payment_method_style_handles( $args, 'woocommerce/checkout' );

		$this->assertSame(
			array( 'shared-editor-style', 'payment-method-editor-style' ),
			$result['editor_style_handles'],
			'Payment method editor styles should be appended without duplicates.'
		);
		$this->assertSame(
			array( 'wc-blocks-style' ),
			$result['style_handles'],
			'Existing block styles should remain unchanged.'
		);
		$this->assertArrayNotHasKey( 'view_style_handles', $result );
	}

	/**
	 * @testdox Should leave unrelated block type arguments unchanged.
	 */
	public function test_leaves_unrelated_block_type_arguments_unchanged(): void {
		$payment_method_registry = new PaymentMethodRegistry();
		$payment_method_registry->register(
			$this->create_style_payment_method(
				array( 'payment-method-style' ),
				array( 'payment-method-editor-style' )
			)
		);
		$api  = $this->get_blocks_payments_api( $payment_method_registry );
		$args = array(
			'style_handles' => array( 'product-style' ),
		);

		$result = $api->maybe_add_payment_method_style_handles( $args, 'woocommerce/product-image' );

		$this->assertSame( $args, $result, 'Unrelated block types should not receive payment method styles.' );
	}

	/**
	 * Provide Cart and Checkout block type names.
	 *
	 * @return array<string, array<string>>
	 */
	public static function cart_and_checkout_block_types() {
		return array(
			'Cart'     => array( 'woocommerce/cart' ),
			'Checkout' => array( 'woocommerce/checkout' ),
		);
	}

	/**
	 * Get the Blocks Payments API (the system under test).
	 *
	 * @param PaymentMethodRegistry $payment_method_registry Payment method registry.
	 * @return Api
	 */
	private function get_blocks_payments_api( $payment_method_registry ): Api {
		return new Api(
			$payment_method_registry,
			$this->createMock( AssetDataRegistry::class )
		);
	}

	/**
	 * Create a style-capable payment method.
	 *
	 * @param string[] $frontend_style_handles Frontend style handles.
	 * @param string[] $admin_style_handles    Admin style handles.
	 * @return AbstractPaymentMethodType
	 */
	private function create_style_payment_method( $frontend_style_handles, $admin_style_handles ) {
		return new class( $frontend_style_handles, $admin_style_handles ) extends AbstractPaymentMethodType {
			/**
			 * Payment method name.
			 *
			 * @var string
			 */
			protected $name = 'test-payment-method';

			/**
			 * Frontend style handles.
			 *
			 * @var string[]
			 */
			private $frontend_style_handles;

			/**
			 * Admin style handles.
			 *
			 * @var string[]
			 */
			private $admin_style_handles;

			/**
			 * Constructor.
			 *
			 * @param string[] $frontend_style_handles Frontend style handles.
			 * @param string[] $admin_style_handles    Admin style handles.
			 */
			public function __construct( $frontend_style_handles, $admin_style_handles ) {
				$this->frontend_style_handles = $frontend_style_handles;
				$this->admin_style_handles    = $admin_style_handles;
			}

			/**
			 * Initialize the payment method.
			 */
			public function initialize() {
			}

			/**
			 * Get frontend style handles.
			 *
			 * @return string[]
			 */
			public function get_payment_method_style_handles() {
				return $this->frontend_style_handles;
			}

			/**
			 * Get admin style handles.
			 *
			 * @return string[]
			 */
			public function get_payment_method_style_handles_for_admin() {
				return $this->admin_style_handles;
			}
		};
	}
}
