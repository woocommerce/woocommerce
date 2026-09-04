<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Settings;

use Automattic\WooCommerce\Blocks\Package as BlocksPackage;
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsController;
use Automattic\WooCommerce\Tests\Internal\Admin\Settings\Mocks\FakePaymentGateway;
use WC_Unit_Test_Case;

/**
 * Tests for the PaymentsController class.
 */
class PaymentsControllerTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var PaymentsController
	 */
	private $sut;

	/**
	 * The ID of the checkout page created for the test.
	 *
	 * @var int
	 */
	private $checkout_page_id;

	/**
	 * The callback used to register the fake gateway, if any.
	 *
	 * @var callable|null
	 */
	private $gateways_filter_callback = null;

	/**
	 * The names of the Checkout block payment method integrations registered by the test.
	 *
	 * @var string[]
	 */
	private $registered_integration_names = array();

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		set_current_screen( 'woocommerce_page_wc-settings' );
		$GLOBALS['current_tab'] = 'checkout';

		$this->checkout_page_id = $this->factory->post->create(
			array(
				'post_type'    => 'page',
				'post_content' => '<!-- wp:woocommerce/checkout --><!-- /wp:woocommerce/checkout -->',
			)
		);
		update_option( 'woocommerce_checkout_page_id', $this->checkout_page_id );

		$this->sut = wc_get_container()->get( PaymentsController::class );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		unset( $GLOBALS['current_tab'] );
		set_current_screen( 'front' );
		delete_option( 'woocommerce_checkout_page_id' );

		if ( null !== $this->gateways_filter_callback ) {
			remove_filter( 'woocommerce_payment_gateways', $this->gateways_filter_callback );
			$this->gateways_filter_callback = null;
		}
		WC()->payment_gateways()->payment_gateways = array();
		WC()->payment_gateways()->init();

		foreach ( $this->registered_integration_names as $name ) {
			if ( $this->get_payment_method_registry()->is_registered( $name ) ) {
				$this->get_payment_method_registry()->unregister( $name );
			}
		}
		$this->registered_integration_names = array();

		parent::tearDown();
	}

	/**
	 * @testdox Should list gateways that have no Checkout block payment method integration.
	 */
	public function test_preload_settings_lists_gateways_without_a_checkout_block_integration(): void {
		$this->register_fake_gateway();
		$this->register_fake_block_payment_integration( 'other-fake-gateway-id' );

		$settings = $this->sut->preload_settings();

		$this->assertContains(
			'fake-gateway-id',
			$settings['woocommerce_payments_checkout_block_compatibility']['incompatible_gateway_ids'],
			'A gateway without a Checkout block integration should be reported as incompatible'
		);
	}

	/**
	 * @testdox Should not list gateways that have a Checkout block payment method integration.
	 */
	public function test_preload_settings_skips_gateways_with_a_checkout_block_integration(): void {
		$this->register_fake_gateway();
		$this->register_fake_block_payment_integration( 'fake-gateway-id' );

		$settings = $this->sut->preload_settings();

		$this->assertNotContains(
			'fake-gateway-id',
			$settings['woocommerce_payments_checkout_block_compatibility']['incompatible_gateway_ids'],
			'A gateway with a Checkout block integration should not be reported as incompatible'
		);
	}

	/**
	 * @testdox Should list incompatible gateways regardless of them being enabled.
	 */
	public function test_preload_settings_lists_incompatible_gateways_that_are_disabled(): void {
		$this->register_fake_gateway( 'no' );
		$this->register_fake_block_payment_integration( 'other-fake-gateway-id' );

		$settings = $this->sut->preload_settings();

		$this->assertContains(
			'fake-gateway-id',
			$settings['woocommerce_payments_checkout_block_compatibility']['incompatible_gateway_ids'],
			'The enabled state of a gateway should not influence the compatibility list'
		);
	}

	/**
	 * @testdox Should not add compatibility data when the checkout page does not use the Checkout block.
	 */
	public function test_preload_settings_adds_no_compatibility_data_for_the_classic_checkout(): void {
		wp_update_post(
			array(
				'ID'           => $this->checkout_page_id,
				'post_content' => '<!-- wp:shortcode -->[woocommerce_checkout]<!-- /wp:shortcode -->',
			)
		);
		$this->register_fake_gateway();

		$settings = $this->sut->preload_settings();

		$this->assertArrayNotHasKey(
			'woocommerce_payments_checkout_block_compatibility',
			$settings,
			'Stores on the classic checkout should not receive compatibility data'
		);
	}

	/**
	 * @testdox Should not add compatibility data outside of the payments settings tab.
	 */
	public function test_preload_settings_adds_no_compatibility_data_outside_the_payments_tab(): void {
		$GLOBALS['current_tab'] = 'general';
		$this->register_fake_gateway();

		$settings = $this->sut->preload_settings();

		$this->assertArrayNotHasKey(
			'woocommerce_payments_checkout_block_compatibility',
			$settings,
			'Compatibility data is only needed by the payments settings page'
		);
	}

	/**
	 * Add a gateway without a Checkout block integration to the list of loaded gateways.
	 *
	 * @param string $enabled Whether the gateway is enabled: 'yes' or 'no'.
	 */
	private function register_fake_gateway( string $enabled = 'yes' ): void {
		$this->gateways_filter_callback = function ( $gateways ) use ( $enabled ) {
			$gateway          = new FakePaymentGateway();
			$gateway->enabled = $enabled;
			$gateways[]       = $gateway;

			return $gateways;
		};
		add_filter( 'woocommerce_payment_gateways', $this->gateways_filter_callback );

		WC()->payment_gateways()->init();
	}

	/**
	 * Register a minimal Checkout block payment method integration.
	 *
	 * @param string $name The integration name, matching the gateway ID it stands for.
	 */
	private function register_fake_block_payment_integration( string $name ): void {
		$integration = new class( $name ) extends AbstractPaymentMethodType {
			/**
			 * Constructor.
			 *
			 * @param string $name The integration name.
			 */
			public function __construct( string $name ) {
				$this->name = $name;
			}

			/**
			 * Initializes the payment method type.
			 */
			public function initialize() {}
		};

		$this->get_payment_method_registry()->register( $integration );
		$this->registered_integration_names[] = $name;
	}

	/**
	 * Get the Checkout block payment method registry.
	 *
	 * @return PaymentMethodRegistry
	 */
	private function get_payment_method_registry(): PaymentMethodRegistry {
		return BlocksPackage::container()->get( PaymentMethodRegistry::class );
	}
}
