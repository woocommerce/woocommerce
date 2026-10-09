<?php
/**
 * Tests for the payment method registry.
 *
 * @package WooCommerce\Tests\Blocks\Payments
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Payments;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;
use Automattic\WooCommerce\Blocks\Payments\PaymentMethodTypeInterface;
use Automattic\WooCommerce\Blocks\Payments\PaymentMethodTypeStyleInterface;
use WC_Unit_Test_Case;

/**
 * Tests for the PaymentMethodRegistry class.
 */
class PaymentMethodRegistryTest extends WC_Unit_Test_Case {

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
	 * @testdox Should provide empty default style handles to existing subclasses.
	 */
	public function test_abstract_payment_method_type_provides_empty_style_handle_defaults(): void {
		$payment_method = new class() extends AbstractPaymentMethodType {
			/**
			 * Initialize the payment method.
			 */
			public function initialize() {
			}
		};

		$this->assertInstanceOf( PaymentMethodTypeStyleInterface::class, $payment_method );
		$this->assertSame( array(), $payment_method->get_payment_method_style_handles() );
		$this->assertSame( array(), $payment_method->get_payment_method_style_handles_for_admin() );
	}

	/**
	 * @testdox Should use frontend style handles as the default for the admin context.
	 */
	public function test_abstract_payment_method_type_uses_frontend_styles_for_admin_by_default(): void {
		$payment_method = new class() extends AbstractPaymentMethodType {
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
				return array( 'payment-method-style' );
			}
		};

		$this->assertSame( array( 'payment-method-style' ), $payment_method->get_payment_method_style_handles_for_admin() );
	}

	/**
	 * @testdox Should collect unique frontend styles from active style-capable payment methods.
	 */
	public function test_collects_unique_frontend_styles_from_active_style_capable_payment_methods(): void {
		$registry = $this->get_payment_method_registry();
		$registry->register(
			$this->create_style_payment_method(
				'first',
				array( 'first-style', '', 'shared-style' ),
				array( 'first-admin-style' )
			)
		);
		$registry->register(
			$this->create_style_payment_method(
				'second',
				array( 'shared-style', 'second-style' ),
				array( 'second-admin-style' )
			)
		);
		$registry->register( $this->create_legacy_payment_method( 'legacy' ) );

		$this->assertSame(
			array( 'first-style', 'shared-style', 'second-style' ),
			$registry->get_all_active_payment_method_style_handles(),
			'Only unique, non-empty frontend style handles should be returned.'
		);
	}

	/**
	 * @testdox Should collect admin styles in the admin context.
	 */
	public function test_collects_admin_styles_in_admin_context(): void {
		set_current_screen( 'edit-post' );
		$registry = $this->get_payment_method_registry();
		$registry->register(
			$this->create_style_payment_method(
				'payment-method',
				array( 'frontend-style' ),
				array( 'admin-style' )
			)
		);

		$this->assertSame(
			array( 'admin-style' ),
			$registry->get_all_active_payment_method_style_handles(),
			'Admin requests should use the payment method admin style handles.'
		);
	}

	/**
	 * @testdox Should ignore styles from inactive payment methods.
	 */
	public function test_ignores_styles_from_inactive_payment_methods(): void {
		$registry = $this->get_payment_method_registry();
		$registry->register(
			$this->create_style_payment_method(
				'inactive',
				array( 'inactive-style' ),
				array( 'inactive-admin-style' ),
				false
			)
		);

		$this->assertSame( array(), $registry->get_all_active_payment_method_style_handles() );
	}

	/**
	 * Get the Payment Method Registry (the system under test).
	 *
	 * @return PaymentMethodRegistry
	 */
	private function get_payment_method_registry(): PaymentMethodRegistry {
		return new PaymentMethodRegistry();
	}

	/**
	 * Create a style-capable payment method.
	 *
	 * @param string   $name                  Payment method name.
	 * @param string[] $frontend_style_handles Frontend style handles.
	 * @param string[] $admin_style_handles    Admin style handles.
	 * @param bool     $active                 Whether the payment method is active.
	 * @return AbstractPaymentMethodType
	 */
	private function create_style_payment_method( $name, $frontend_style_handles, $admin_style_handles, $active = true ) {
		return new class( $name, $frontend_style_handles, $admin_style_handles, $active ) extends AbstractPaymentMethodType {
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
			 * Whether the payment method is active.
			 *
			 * @var bool
			 */
			private $active;

			/**
			 * Constructor.
			 *
			 * @param string   $name                   Payment method name.
			 * @param string[] $frontend_style_handles Frontend style handles.
			 * @param string[] $admin_style_handles    Admin style handles.
			 * @param bool     $active                 Whether the payment method is active.
			 */
			public function __construct( $name, $frontend_style_handles, $admin_style_handles, $active ) {
				$this->name                   = $name;
				$this->frontend_style_handles = $frontend_style_handles;
				$this->admin_style_handles    = $admin_style_handles;
				$this->active                 = $active;
			}

			/**
			 * Initialize the payment method.
			 */
			public function initialize() {
			}

			/**
			 * Return whether the payment method is active.
			 *
			 * @return bool
			 */
			public function is_active() {
				return $this->active;
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

	/**
	 * Create a payment method that implements only the existing interface.
	 *
	 * @param string $name Payment method name.
	 * @return PaymentMethodTypeInterface
	 */
	private function create_legacy_payment_method( $name ) {
		return new class( $name ) implements PaymentMethodTypeInterface {
			/**
			 * Payment method name.
			 *
			 * @var string
			 */
			private $name;

			/**
			 * Constructor.
			 *
			 * @param string $name Payment method name.
			 */
			public function __construct( $name ) {
				$this->name = $name;
			}

			/**
			 * Get the payment method name.
			 *
			 * @return string
			 */
			public function get_name() {
				return $this->name;
			}

			/**
			 * Initialize the payment method.
			 */
			public function initialize() {
			}

			/**
			 * Return whether the payment method is active.
			 *
			 * @return bool
			 */
			public function is_active() {
				return true;
			}

			/**
			 * Get frontend payment method script handles.
			 *
			 * @return string[]
			 */
			public function get_payment_method_script_handles() {
				return array();
			}

			/**
			 * Get admin payment method script handles.
			 *
			 * @return string[]
			 */
			public function get_payment_method_script_handles_for_admin() {
				return array();
			}

			/**
			 * Get payment method data.
			 *
			 * @return array
			 */
			public function get_payment_method_data() {
				return array();
			}

			/**
			 * Get supported features.
			 *
			 * @return string[]
			 */
			public function get_supported_features() {
				return array();
			}

			/**
			 * Get frontend script handles.
			 *
			 * @return string[]
			 */
			public function get_script_handles() {
				return array();
			}

			/**
			 * Get editor script handles.
			 *
			 * @return string[]
			 */
			public function get_editor_script_handles() {
				return array();
			}

			/**
			 * Get script data.
			 *
			 * @return array
			 */
			public function get_script_data() {
				return array();
			}
		};
	}
}
