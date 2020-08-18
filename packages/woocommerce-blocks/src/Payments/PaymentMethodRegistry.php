<?php
/**
 * Holds data about registered payment methods.
 *
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks\Payments;

/**
 * Class used for interacting with payment method types.
 *
 * @since 2.6.0
 */
final class PaymentMethodRegistry {

	/**
	 * Registered payment methods, as `$name => $instance` pairs.
	 *
	 * @var PaymentMethodTypeInterface[]
	 */
	private $registered_payment_methods = [];

	/**
	 * Registers a payment method.
	 *
	 * @param PaymentMethodTypeInterface $payment_method_type An instance of PaymentMethodTypeInterface.
	 *
	 * @return boolean True means registered successfully.
	 */
	public function register( PaymentMethodTypeInterface $payment_method_type ) {
		$name = $payment_method_type->get_name();
		if ( $this->is_registered( $name ) ) {
			/* translators: %s: Payment method name. */
			_doing_it_wrong( __METHOD__, esc_html( sprintf( __( 'Payment method "%s" is already registered.', 'woocommerce' ), $name ) ), '2.5.0' );
			return false;
		}
		$this->registered_payment_methods[ $name ] = $payment_method_type;
		return true;
	}

	/**
	 * Initializes all payment method types.
	 */
	public function initialize() {
		/**
		 * Hook: payment_method_type_registration.
		 *
		 * Runs before payment methods are initialized allowing new methods to be registered for use.
		 *
		 * @param PaymentMethodRegistry $this Instance of the PaymentMethodRegistry class which exposes the
		 *                                    PaymentMethodRegistry::register() method.
		 */
		do_action( 'woocommerce_blocks_payment_method_type_registration', $this );

		$registered_payment_method_types = $this->get_all_registered();

		foreach ( $registered_payment_method_types as $registered_type ) {
			$registered_type->initialize();
		}
	}

	/**
	 * Un-register a payment method.
	 *
	 * @param string|PaymentMethodTypeInterface $name Payment method name, or alternatively a PaymentMethodTypeInterface instance.
	 * @return boolean True means unregistered successfully.
	 */
	public function unregister( $name ) {
		if ( $name instanceof PaymentMethodTypeInterface ) {
			$name = $name->get_name();
		}

		if ( ! $this->is_registered( $name ) ) {
			/* translators: %s: Payment method name. */
			_doing_it_wrong( __METHOD__, esc_html( sprintf( __( 'Payment method "%s" is not registered.', 'woocommerce' ), $name ) ), '2.5.0' );
			return false;
		}

		$unregistered_payment_method = $this->registered_payment_methods[ $name ];
		unset( $this->registered_payment_methods[ $name ] );

		return $unregistered_payment_method;
	}

	/**
	 * Retrieves a registered payment method.
	 *
	 * @param string $name Payment method name.
	 * @return PaymentMethodTypeInterface|null The registered payment method, or null if it is not registered.
	 */
	public function get_registered( $name ) {
		if ( ! $this->is_registered( $name ) ) {
			return null;
		}

		return $this->registered_payment_methods[ $name ];
	}

	/**
	 * Retrieves all registered payment methods.
	 *
	 * @return PaymentMethodTypeInterface[]
	 */
	public function get_all_registered() {
		return $this->registered_payment_methods;
	}

	/**
	 * Checks if a payment method is registered.
	 *
	 * @param string $name Payment method name.
	 * @return bool True if the payment method is registered, false otherwise.
	 */
	public function is_registered( $name ) {
		return isset( $this->registered_payment_methods[ $name ] );
	}

	/**
	 * Retrieves all registered payment methods that are also active.
	 *
	 * @return PaymentMethodTypeInterface[]
	 */
	public function get_all_active_registered() {
		return array_filter(
			$this->get_all_registered(),
			function( $payment_method ) {
				return $payment_method->is_active();
			}
		);
	}

	/**
	 * Gets an array of all registered payment method script handles.
	 *
	 * @return string[]
	 */
	public function get_all_registered_script_handles() {
		$script_handles  = [];
		$payment_methods = $this->get_all_active_registered();

		foreach ( $payment_methods as $payment_method ) {
			$script_handles = array_merge(
				$script_handles,
				is_admin() ? $payment_method->get_payment_method_script_handles_for_admin() : $payment_method->get_payment_method_script_handles()
			);
		}

		return array_unique( array_filter( $script_handles ) );
	}

	/**
	 * Gets an array of all registered payment method script data.
	 *
	 * @return array
	 */
	public function get_all_registered_script_data() {
		$script_data     = [];
		$payment_methods = $this->get_all_active_registered();

		foreach ( $payment_methods as $payment_method ) {
			$script_data[ $payment_method->get_name() . '_data' ] = $payment_method->get_payment_method_data();
		}

		return array_filter( $script_data );
	}
}
