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
 * @since 11.2.0
 */
class FulfillmentsCsvImporterController {

	/**
	 * Importer ID listed under Tools > Import.
	 */
	private const WP_IMPORTER_ID = 'woocommerce_fulfillment_csv';

	/**
	 * Whether the React assets are already enqueued for this page load.
	 *
	 * @var bool
	 */
	private bool $assets_enqueued = false;

	/**
	 * Register hooks. Called by FulfillmentsController when the feature is enabled.
	 *
	 * @since 11.2.0
	 */
	public function register(): void {
		// The React script (`fulfillments-importer`) injects the "Import fulfillments" trigger
		// next to the "Add order" page-title-action link on the orders list. Here we only need
		// to mount the modal slot and enqueue the assets.
		add_action( 'admin_footer', array( $this, 'render_modal_slot' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_init', array( $this, 'register_wp_importer' ) );
		add_action( 'load-importer-' . self::WP_IMPORTER_ID, array( $this, 'redirect_to_orders_screen' ) );
		add_action( ImportSession::CLEANUP_HOOK, array( ImportSession::class, 'handle_cleanup_hook' ), 10, 4 );
	}

	/**
	 * List the importer on Tools > Import alongside the product and tax importers.
	 *
	 * The wizard itself lives on the orders screen, so "Run Importer" redirects there.
	 *
	 * @since 11.2.0
	 */
	public function register_wp_importer(): void {
		if ( ! defined( 'WP_LOAD_IMPORTERS' ) || ! function_exists( 'register_importer' ) ) {
			return;
		}
		register_importer(
			self::WP_IMPORTER_ID,
			__( 'WooCommerce fulfillments (CSV)', 'woocommerce' ),
			__( 'Import <strong>order fulfillments</strong> to your store via a CSV file.', 'woocommerce' ),
			array( $this, 'render_importer_link' )
		);
	}

	/**
	 * Send "Run Importer" to the orders screen. Fires on load-importer-{id},
	 * before the admin header is rendered, so the redirect can still happen.
	 *
	 * @since 11.2.0
	 */
	public function redirect_to_orders_screen(): void {
		wp_safe_redirect( $this->get_importer_url() );
		exit;
	}

	/**
	 * Fallback importer-page body in case the pre-render redirect did not run.
	 *
	 * @since 11.2.0
	 */
	public function render_importer_link(): void {
		printf(
			'<div class="wrap"><p><a href="%s">%s</a></p></div>',
			esc_url( $this->get_importer_url() ),
			esc_html__( 'Open the fulfillments importer on the orders screen.', 'woocommerce' )
		);
	}

	/**
	 * Orders-list URL that auto-opens the import wizard.
	 *
	 * @return string
	 */
	private function get_importer_url(): string {
		$base = OrderUtil::custom_orders_table_usage_is_enabled()
			? admin_url( 'admin.php?page=wc-orders' )
			: admin_url( 'edit.php?post_type=shop_order' );
		return add_query_arg( 'fulfillments_importer', 'open', $base );
	}

	/**
	 * Render the React mount slot once per page load on the orders list.
	 *
	 * @since 11.2.0
	 */
	public function render_modal_slot(): void {
		if ( ! $this->should_render_importer() ) {
			return;
		}
		echo '<div id="wc_fulfillments_importer_panel_container"></div>';
	}

	/**
	 * Register and enqueue the React script and style on the orders list screen.
	 *
	 * @since 11.2.0
	 */
	public function enqueue_assets(): void {
		if ( $this->assets_enqueued ) {
			return;
		}
		if ( ! $this->should_render_importer() ) {
			return;
		}

		// Not WCAdminAssets::register_style(): that derives the handle from the style
		// name ('wc-admin-style'), which the fulfillments drawer already registers on
		// this screen, so this stylesheet would be silently dropped.
		$style_assets_filename = WCAdminAssets::get_script_asset_filename( 'fulfillments-importer', 'style' );
		$style_assets          = require WC_ADMIN_ABSPATH . WC_ADMIN_DIST_CSS_FOLDER . 'fulfillments-importer/' . $style_assets_filename;
		wp_enqueue_style(
			'wc-admin-fulfillments-importer',
			WCAdminAssets::get_url( 'fulfillments-importer/style', 'css' ),
			array( 'wp-components' ),
			WCAdminAssets::get_file_version( 'css', $style_assets['version'] )
		);
		wp_style_add_data( 'wc-admin-fulfillments-importer', 'rtl', 'replace' );

		WCAdminAssets::register_script( 'wp-admin-scripts', 'fulfillments-importer', true );

		wp_localize_script(
			'wc-admin-fulfillments-importer',
			'wcFulfillmentsImporterSettings',
			array(
				'importRoute' => '/wc/v3/fulfillments/import',
				'chunkSize'   => FulfillmentsCsvImporter::resolve_chunk_size(),
				'maxRows'     => FulfillmentsCsvImporter::MAX_IMPORT_ROWS,
				'providers'   => $this->get_provider_list_for_js(),
			)
		);

		$this->assets_enqueued = true;
	}

	/**
	 * Should the importer UI render on the current screen?
	 *
	 * Admin orders list only, for users with the manage_woocommerce capability. The orders
	 * list lives on a different screen depending on whether HPOS is enabled.
	 *
	 * @since 11.2.0
	 *
	 * @return bool
	 */
	private function should_render_importer(): bool {
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
