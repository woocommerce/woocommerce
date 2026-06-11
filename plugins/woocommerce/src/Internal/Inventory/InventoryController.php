<?php
/**
 * InventoryController class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Inventory;

defined( 'ABSPATH' ) || exit;

/**
 * Controller for POS location stock.
 *
 * @internal
 */
class InventoryController {

	/**
	 * Feature ID used by FeaturesController.
	 */
	public const FEATURE_ID = 'pos_location_stock';

	/**
	 * Option key used as the feature flag.
	 */
	public const FEATURE_OPTION = 'woocommerce_pos_location_stock';

	public const ORDER_LOCATION_META = '_inventory_location';

	/**
	 * Feature and configuration gate.
	 *
	 * @var LocationStockGate
	 */
	private LocationStockGate $gate;

	/**
	 * Admin product editor controller.
	 *
	 * @var LocationStockAdminController
	 */
	private LocationStockAdminController $admin_controller;

	/**
	 * Location stock installer.
	 *
	 * @var LocationStockInstaller
	 */
	private LocationStockInstaller $location_stock_installer;

	/**
	 * REST API hooks.
	 *
	 * @var LocationStockRestApiHooks
	 */
	private LocationStockRestApiHooks $rest_api_hooks;

	/**
	 * Order stock controller.
	 *
	 * @var LocationStockOrderController
	 */
	private LocationStockOrderController $order_controller;

	/**
	 * Whether feature hooks have already been registered in this request.
	 *
	 * @var bool
	 */
	private bool $feature_hooks_registered = false;

	/**
	 * Initialize dependencies.
	 *
	 * @param LocationStockGate            $gate Feature and configuration gate.
	 * @param LocationStockAdminController $admin_controller Admin product editor controller.
	 * @param LocationStockInstaller       $location_stock_installer Location stock installer.
	 * @param LocationStockRestApiHooks    $rest_api_hooks REST API hooks.
	 * @param LocationStockOrderController $order_controller Order stock controller.
	 *
	 * @internal
	 */
	final public function init( LocationStockGate $gate, LocationStockAdminController $admin_controller, LocationStockInstaller $location_stock_installer, LocationStockRestApiHooks $rest_api_hooks, LocationStockOrderController $order_controller ): void {
		$this->gate                     = $gate;
		$this->admin_controller         = $admin_controller;
		$this->location_stock_installer = $location_stock_installer;
		$this->rest_api_hooks           = $rest_api_hooks;
		$this->order_controller         = $order_controller;
	}

	/**
	 * Register hooks.
	 */
	public function register(): void {
		$this->location_stock_installer->register();
		add_action( 'init', array( $this, 'register_feature_hooks' ), 20 );
	}

	/**
	 * Check whether the POS location stock feature flag is enabled.
	 */
	public function feature_is_enabled(): bool {
		return $this->gate->feature_is_enabled();
	}

	/**
	 * Register behavior hooks only when the feature flag is enabled.
	 */
	public function register_feature_hooks(): void {
		if ( $this->feature_hooks_registered || ! $this->feature_is_enabled() ) {
			return;
		}

		$this->feature_hooks_registered = true;
		$this->location_stock_installer->maybe_create_db_tables();

		$this->admin_controller->register();
		$this->rest_api_hooks->register();
		$this->order_controller->register();
	}
}
