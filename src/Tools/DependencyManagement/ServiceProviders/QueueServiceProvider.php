<?php
/**
 * QueueServiceProvider class file.
 *
 * @package Automattic\WooCommerce\Tools\DependencyManagement\ServiceProviders
 */

namespace Automattic\WooCommerce\Tools\DependencyManagement\ServiceProviders;

use League\Container\ServiceProvider\AbstractServiceProvider;
use \ReflectionClass;
use \ReflectionException;
use \WC_Action_Queue;
use \WC_Queue_Interface;

/**
 * Service provider for WC_Queue_Interface.
 */
class QueueServiceProvider extends AbstractServiceProvider {

	/**
	 * The queue class that will be instantiated unless something different is specified via woocommerce_queue_class filter.
	 */
	const DEFAULT_QUEUE_CLASS = WC_Action_Queue::class;

	/**
	 * The classes/interfaces that are serviced by this service provider.
	 *
	 * @var array
	 */
	protected $provides = array(
		WC_Queue_Interface::class,
	);

	/**
	 * Register the function that will create the singleton instance of WC_Queue_Interface.
	 */
	public function register() {
		$container = $this->getContainer();

		$container->share(
			WC_Queue_Interface::class,
			function() {
				return $this->get_queue_instance();
			}
		);
	}

	/**
	 * Create a new instance of a class implementing WC_Queue_Interface.
	 * It will be an instance of {DEFAULT_QUEUE_CLASS} unless something different is specified via woocommerce_queue_class filter.
	 *
	 * @return WC_Queue_Interface
	 */
	private function get_queue_instance() {
		if ( ! did_action( 'plugins_loaded' ) ) {
			$this->error_bad_timing();
		}

		$queue_class = apply_filters( 'woocommerce_queue_class', self::DEFAULT_QUEUE_CLASS );
		$rc          = null;
		try {
			$rc = new ReflectionClass( $queue_class );
		} catch ( ReflectionException $exception ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// $rc will remain null and this will be enough to detect the error later.
		}

		if ( is_null( $rc ) || ! $rc->implementsInterface( WC_Queue_Interface::class ) ) {
			$this->error_bad_class();
			$queue_class = self::DEFAULT_QUEUE_CLASS;
		}

		return $this->getContainer()->get( $queue_class );
	}

	/**
	 * Throw a "plugins not loaded" notice.
	 */
	private function error_bad_timing() {
		wc_doing_it_wrong(
			__FUNCTION__,
			__( 'Queue instance should not be obtained before plugins_loaded.', 'woocommerce' ),
			'3.5.0'
		);
	}

	/**
	 * Throw a "invalid class name" notice.
	 */
	private function error_bad_class() {
		wc_doing_it_wrong(
			__FUNCTION__,
			sprintf(
			/* translators: %1$s: Interface name, %2$s: Default class name */
				__( 'The class attached to the "woocommerce_queue_class" filter is invalid or does not implement the %1$s interface. The default %2$s class will be used instead.', 'woocommerce' ),
				WC_Queue_Interface::class,
				self::DEFAULT_QUEUE_CLASS
			),
			'3.5.0'
		);
	}
}
