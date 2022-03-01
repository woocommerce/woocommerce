<?php

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Admin\Loader;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;

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
		return __( 'Add my products', 'woocommerce-admin' );
	}

	/**
	 * Content.
	 *
	 * @return string
	 */
	public function get_content() {
		return __(
			'Start by adding the first product to your store. You can add your products manually, via CSV, or import them from another service.',
			'woocommerce-admin'
		);
	}

	/**
	 * Time.
	 *
	 * @return string
	 */
	public function get_time() {
		return __( '1 minute per product', 'woocommerce-admin' );
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

		if ( ! $this->is_active() || $this->is_complete() ) {
			return;
		}

		$script_assets_filename = Loader::get_script_asset_filename( 'wp-admin-scripts', 'onboarding-product-notice' );
		$script_assets          = require WC_ADMIN_ABSPATH . WC_ADMIN_DIST_JS_FOLDER . 'wp-admin-scripts/' . $script_assets_filename;

		wp_enqueue_script(
			'onboarding-product-notice',
			Loader::get_url( 'wp-admin-scripts/onboarding-product-notice', 'js' ),
			array_merge( array( WC_ADMIN_APP ), $script_assets ['dependencies'] ),
			WC_VERSION,
			true
		);
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

		$script_assets_filename = Loader::get_script_asset_filename( 'wp-admin-scripts', 'onboarding-product-import-notice' );
		$script_assets          = require WC_ADMIN_ABSPATH . WC_ADMIN_DIST_JS_FOLDER . 'wp-admin-scripts/' . $script_assets_filename;

		wp_enqueue_script(
			'onboarding-product-import-notice',
			Loader::get_url( 'wp-admin-scripts/onboarding-product-import-notice', 'js' ),
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
