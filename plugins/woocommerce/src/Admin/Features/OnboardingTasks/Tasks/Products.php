<?php

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;
use Automattic\WooCommerce\Internal\Admin\WCAdminAssets;

/**
 * Products Task
 */
class Products extends Task {

	/**
	 * Constructor
	 *
	 * @param TaskList $task_list Parent task list.
	 */
	public function __construct( $task_list ) {
		parent::__construct( $task_list );
		add_action( 'admin_enqueue_scripts', array( $this, 'possibly_add_manual_return_notice_script' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'possibly_add_import_return_notice_script' ) );
	}

	/**
	 * ID.
	 *
	 * @return string
	 */
	public function get_id() {
		return 'products';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		if ( count( $this->task_list->get_sections() ) > 0 && ! $this->is_complete() ) {
			return __( 'Create or upload your first products', 'woocommerce' );
		}
		if ( true === $this->get_parent_option( 'use_completed_title' ) ) {
			if ( $this->is_complete() ) {
				return __( 'You added products', 'woocommerce' );
			}
			return __( 'Add products', 'woocommerce' );
		}
		return __( 'Add my products', 'woocommerce' );
	}

	/**
	 * Content.
	 *
	 * @return string
	 */
	public function get_content() {
		if ( count( $this->task_list->get_sections() ) > 0 ) {
			return __( 'Add products to sell and build your catalog.', 'woocommerce' );
		}
		return __(
			'Start by adding the first product to your store. You can add your products manually, via CSV, or import them from another service.',
			'woocommerce'
		);
	}

	/**
	 * Time.
	 *
	 * @return string
	 */
	public function get_time() {
		return __( '1 minute per product', 'woocommerce' );
	}

	/**
	 * Task completion.
	 *
	 * @return bool
	 */
	public function is_complete() {
		return self::has_products();
	}

	/**
	 * Addtional data.
	 *
	 * @return array
	 */
	public function get_additional_data() {
		return array(
			'has_products' => self::has_products(),
		);
	}


	/**
	 * Adds a return to task list notice when completing the manual product task.
	 *
	 * @param string $hook Page hook.
	 */
	public function possibly_add_manual_return_notice_script( $hook ) {
		global $post;
		if ( 'post.php' !== $hook || 'product' !== $post->post_type ) {
			return;
		}

		if ( ! $this->is_active() || ! $this->is_complete() ) {
			return;
		}

		$script_assets_filename = WCAdminAssets::get_script_asset_filename( 'wp-admin-scripts', 'onboarding-product-notice' );
		$script_assets          = require WC_ADMIN_ABSPATH . WC_ADMIN_DIST_JS_FOLDER . 'wp-admin-scripts/' . $script_assets_filename;

		wp_enqueue_script(
			'onboarding-product-notice',
			WCAdminAssets::get_url( 'wp-admin-scripts/onboarding-product-notice', 'js' ),
			array_merge( array( WC_ADMIN_APP ), $script_assets ['dependencies'] ),
			WC_VERSION,
			true
		);

		// Clear the active task transient to only show notice once per active session.
		delete_transient( self::ACTIVE_TASK_TRANSIENT );
	}

	/**
	 * Adds a return to task list notice when completing the import product task.
	 *
	 * @param string $hook Page hook.
	 */
	public function possibly_add_import_return_notice_script( $hook ) {
		$step = isset( $_GET['step'] ) ? $_GET['step'] : ''; // phpcs:ignore csrf ok, sanitization ok.

		if ( 'product_page_product_importer' !== $hook || 'done' !== $step ) {
			return;
		}

		if ( ! $this->is_active() || $this->is_complete() ) {
			return;
		}

		$script_assets_filename = WCAdminAssets::get_script_asset_filename( 'wp-admin-scripts', 'onboarding-product-import-notice' );
		$script_assets          = require WC_ADMIN_ABSPATH . WC_ADMIN_DIST_JS_FOLDER . 'wp-admin-scripts/' . $script_assets_filename;

		wp_enqueue_script(
			'onboarding-product-import-notice',
			WCAdminAssets::get_url( 'wp-admin-scripts/onboarding-product-import-notice', 'js' ),
			array_merge( array( WC_ADMIN_APP ), $script_assets ['dependencies'] ),
			WC_VERSION,
			true
		);
	}

	/**
	 * Check if the store has any published products.
	 *
	 * @return bool
	 */
	public static function has_products() {
		$product_query = new \WC_Product_Query(
			array(
				'limit'  => 1,
				'return' => 'ids',
				'status' => array( 'publish' ),
			)
		);
		$products      = $product_query->get_products();

		return 0 !== count( $products );
	}
}
