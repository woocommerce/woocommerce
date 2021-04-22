<?php
/**
 * ClassThatDependsOnLegacyCode class file
 */

namespace Automattic\WooCommerce\Tests\Proxies\ExampleClasses;

use Automattic\WooCommerce\Proxies\LegacyProxy;

/**
 * An example class that uses the legacy proxy both from a dependency injected proxy and from the helper methods in the WooCommerce class.
 */
class ClassThatDependsOnLegacyCode {

	/**
	 * The injected LegacyProxy.
	 *
	 * @var LegacyProxy
	 */
	private $legacy_proxy;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param LegacyProxy $legacy_proxy The instance of LegacyProxy to use.
	 */
	final public function init( LegacyProxy $legacy_proxy ) {
		$this->legacy_proxy = $legacy_proxy;
	}

	/**
	 * Use proxy's 'call_function' from the injected proxy.
	 *
	 * @param string $function Function to call.
	 * @param mixed  ...$parameters Parameters to pass to the function.
	 *
	 * @return mixed The result from the function.
	 */
	public function call_legacy_function_using_injected_proxy( $function, ...$parameters ) {
		return $this->legacy_proxy->call_function( $function, ...$parameters );
	}

	/**
	 * Use proxy's 'call_function' using 'WC()->call_function'.
	 *
	 * @param string $function Function to call.
	 * @param mixed  ...$parameters Parameters to pass to the function.
	 *
	 * @return mixed The result from the function.
	 */
	public function call_legacy_function_using_woocommerce_class( $function, ...$parameters ) {
		return WC()->call_function( $function, ...$parameters );
	}

	/**
	 * Use proxy's 'call_static' from the injected proxy.
	 *
	 * @param string $class_name Class containing the static method to call.
	 * @param string $method_name Static method to call.
	 * @param mixed  ...$parameters Parameters to pass to the method.
	 *
	 * @return mixed The result from the method.
	 */
	public function call_static_method_using_injected_proxy( $class_name, $method_name, ...$parameters ) {
		return $this->legacy_proxy->call_static( $class_name, $method_name, ...$parameters );
	}

	/**
	 * Use proxy's 'call_static' using 'WC()->call_function'.
	 *
	 * @param string $class_name Class containing the static method to call.
	 * @param string $method_name Static method to call.
	 * @param mixed  ...$parameters Parameters to pass to the method.
	 *
	 * @return mixed The result from the method.
	 */
	public function call_static_method_using_woocommerce_class( $class_name, $method_name, ...$parameters ) {
		return WC()->call_static( $class_name, $method_name, ...$parameters );
	}

	/**
	 * Use proxy's 'get_instance_of' from the injected proxy.
	 *
	 * @param string $class_name The name of the class to get an instance of.
	 * @param mixed  ...$args Extra arguments for 'get_instance_of'.
	 *
	 * @return object The instance obtained.
	 */
	public function get_instance_of_using_injected_proxy( string $class_name, ...$args ) {
		return $this->legacy_proxy->get_instance_of( $class_name, ...$args );
	}

	/**
	 * Use proxy's 'get_instance_of' using 'WC()->call_function'.
	 *
	 * @param string $class_name The name of the class to get an instance of.
	 * @param mixed  ...$args Extra arguments for 'get_instance_of'.
	 *
	 * @return object The instance obtained.
	 */
	public function get_instance_of_using_woocommerce_class( string $class_name, ...$args ) {
		return WC()->get_instance_of( $class_name, ...$args );
	}
}
