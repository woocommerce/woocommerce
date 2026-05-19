<?php
/**
 * Admin glue for the Fulfillments CSV importer React UI.
 *
 * @package Automattic\WooCommerce\Admin\Features\Fulfillments\Importer
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Admin\Features\Fulfillments\Importer;

use Automattic\WooCommerce\Admin\Features\Fulfillments\FulfillmentUtils;
use Automattic\WooCommerce\Internal\Admin\WCAdminAssets;
use Automattic\WooCommerce\Utilities\OrderUtil;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the orders-list "Import fulfillments" trigger and a footer slot div
 * into which the `fulfillments-importer` React script mounts the import modal.
 *
 * The actual import is handled by FulfillmentsImporterRestController.
 *
 * @since 10.9.0
 */
class FulfillmentsCsvImporterController {

	/**
	 * Whether the React assets are already enqueued for this page load.
	 *
	 * @var bool
	 */
	private bool $assets_enqueued = false;

	/**
	 * Register hooks. Called by FulfillmentsController when the feature is enabled.
	 */
	public function register(): void {
		// The React script (`fulfillments-importer`) injects the "Import fulfillments" trigger
		// next to the "Add order" page-title-action link on the orders list. Here we only need
		// to mount the modal slot and enqueue the assets.
		add_action( 'admin_footer', array( $this, 'render_modal_slot' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Render the React mount slot once per page load on the orders list.
	 */
	public function render_modal_slot(): void {
		if ( ! $this->should_render_importer() ) {
			return;
		}
		echo '<div id="wc_fulfillments_importer_panel_container"></div>';
	}

	/**
	 * Register and enqueue the React script and style on the orders list screen.
	 */
	public function enqueue_assets(): void {
		if ( $this->assets_enqueued ) {
			return;
		}
		if ( ! $this->should_render_importer() ) {
			return;
		}

		WCAdminAssets::register_style( 'fulfillments-importer', 'style', array( 'wp-components' ) );
		WCAdminAssets::register_script( 'wp-admin-scripts', 'fulfillments-importer', true );

		wp_localize_script(
			'wc-admin-fulfillments-importer',
			'wcFulfillmentsImporterSettings',
			array(
				'restNamespace' => 'wc/v3',
				'importRoute'   => '/wc/v3/fulfillments/import',
				'providers'     => $this->get_provider_list_for_js(),
			)
		);

		$this->assets_enqueued = true;
	}

	/**
	 * Should the importer UI render on the current screen?
	 *
	 * Mirrors the guard used by {@see FulfillmentsRenderer::should_render_fulfillment_drawer()}:
	 * admin orders-list only, with the manage_woocommerce capability.
	 *
	 * @return bool
	 */
	protected function should_render_importer(): bool {
		if ( ! is_admin() || ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return false;
		}

		$screen = get_current_screen();
		if ( ! $screen || ! $screen->id ) {
			return false;
		}

		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			return 'woocommerce_page_wc-orders' === $screen->id;
		}

		return 'edit-shop_order' === $screen->id;
	}

	/**
	 * Build a lightweight list of shipping providers for the JS bundle.
	 *
	 * @return array<int, array{key:string,label:string}>
	 */
	private function get_provider_list_for_js(): array {
		$out = array();
		foreach ( FulfillmentUtils::get_shipping_providers() as $provider ) {
			$out[] = array(
				'key'   => (string) $provider->get_key(),
				'label' => (string) $provider->get_name(),
			);
		}
		return $out;
	}
}
