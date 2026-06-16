<?php
/**
 * MultiCurrencyTrackingController class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency;

use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyStateBuilderFactory;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyTrackingOrderCountProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyTrackingProjectionService;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * Registers native multi-currency tracker data when core owns multi-currency.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyTrackingController implements RegisterHooksInterface {

	private const TRACKER_HOOK = 'woocommerce_tracker_data';

	/**
	 * Runtime owner arbiter.
	 *
	 * @var MultiCurrencyRuntimeArbiter
	 */
	private MultiCurrencyRuntimeArbiter $arbiter;

	/**
	 * Tracking projection service.
	 *
	 * @var MultiCurrencyTrackingProjectionService|null
	 */
	private ?MultiCurrencyTrackingProjectionService $tracking_projection_service = null;

	/**
	 * Tracking order-count projection service.
	 *
	 * @var MultiCurrencyTrackingOrderCountProjectionService|null
	 */
	private ?MultiCurrencyTrackingOrderCountProjectionService $order_count_projection_service = null;

	/**
	 * HPOS enabled resolver.
	 *
	 * @var callable|null
	 */
	private $hpos_enabled_resolver = null;

	/**
	 * State builder factory.
	 *
	 * @var MultiCurrencyStateBuilderFactory
	 */
	private MultiCurrencyStateBuilderFactory $state_builder_factory;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param MultiCurrencyRuntimeArbiter      $arbiter               Runtime owner arbiter.
	 * @param MultiCurrencyStateBuilderFactory $state_builder_factory State builder factory.
	 */
	final public function init( MultiCurrencyRuntimeArbiter $arbiter, MultiCurrencyStateBuilderFactory $state_builder_factory ): void {
		$this->arbiter               = $arbiter;
		$this->state_builder_factory = $state_builder_factory;
	}

	/**
	 * Set the tracking projection service.
	 *
	 * @internal Used by tests and future explicit bootstrap definitions.
	 *
	 * @param MultiCurrencyTrackingProjectionService $tracking_projection_service Tracking projection service.
	 */
	public function set_tracking_projection_service( MultiCurrencyTrackingProjectionService $tracking_projection_service ): void {
		$this->tracking_projection_service = $tracking_projection_service;
	}

	/**
	 * Set the tracking order-count projection service.
	 *
	 * @internal Used by tests and future explicit bootstrap definitions.
	 *
	 * @param MultiCurrencyTrackingOrderCountProjectionService $order_count_projection_service Order-count projection service.
	 */
	public function set_order_count_projection_service( MultiCurrencyTrackingOrderCountProjectionService $order_count_projection_service ): void {
		$this->order_count_projection_service = $order_count_projection_service;
	}

	/**
	 * Set the HPOS enabled resolver.
	 *
	 * @internal Used by tests and future explicit bootstrap definitions.
	 *
	 * @param callable $hpos_enabled_resolver Resolver returning true when HPOS order storage is enabled.
	 */
	public function set_hpos_enabled_resolver( callable $hpos_enabled_resolver ): void {
		$this->hpos_enabled_resolver = $hpos_enabled_resolver;
	}

	/**
	 * Register tracker data hooks.
	 */
	public function register() {
		if ( ! $this->arbiter->should_core_register() ) {
			return;
		}

		$this->add_filter_once( self::TRACKER_HOOK, array( $this, 'add_tracker_data' ), 50 );
	}

	/**
	 * Add native multi-currency data to the WooCommerce tracker payload.
	 *
	 * @internal
	 *
	 * @param array<string,mixed> $data WooCommerce tracker data.
	 * @return array<string,mixed>
	 */
	public function add_tracker_data( array $data ): array {
		global $wpdb;

		$order_count_service = $this->get_order_count_projection_service();
		$query               = $order_count_service->get_order_count_query( $this->is_hpos_enabled() );
		$rows                = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$order_counts        = $order_count_service->aggregate_order_count_rows( is_array( $rows ) ? $rows : array() );

		return $this->get_tracking_projection_service()->project_tracker_data( $data, $order_counts );
	}

	/**
	 * Get the tracking projection service.
	 *
	 * @return MultiCurrencyTrackingProjectionService
	 */
	private function get_tracking_projection_service(): MultiCurrencyTrackingProjectionService {
		if ( null === $this->tracking_projection_service ) {
			$this->tracking_projection_service = new MultiCurrencyTrackingProjectionService(
				$this->state_builder_factory->create()
			);
		}

		return $this->tracking_projection_service;
	}

	/**
	 * Get the tracking order-count projection service.
	 *
	 * @return MultiCurrencyTrackingOrderCountProjectionService
	 */
	private function get_order_count_projection_service(): MultiCurrencyTrackingOrderCountProjectionService {
		if ( null === $this->order_count_projection_service ) {
			$this->order_count_projection_service = new MultiCurrencyTrackingOrderCountProjectionService();
		}

		return $this->order_count_projection_service;
	}

	/**
	 * Tell whether HPOS order storage is enabled.
	 *
	 * @return bool
	 */
	private function is_hpos_enabled(): bool {
		if ( null !== $this->hpos_enabled_resolver ) {
			return (bool) call_user_func( $this->hpos_enabled_resolver );
		}

		return class_exists( OrderUtil::class ) && OrderUtil::custom_orders_table_usage_is_enabled();
	}

	/**
	 * Register a filter only once for this controller instance.
	 *
	 * @param string   $hook          Hook name.
	 * @param callable $callback      Hook callback.
	 * @param int      $priority      Hook priority.
	 * @param int      $accepted_args Accepted argument count.
	 */
	private function add_filter_once( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): void {
		if ( false === has_filter( $hook, $callback ) ) {
			add_filter( $hook, $callback, $priority, $accepted_args );
		}
	}
}
