<?php

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Admin\Loader;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;

/**
 * Products Task
 */
class Products {
	/**
	 * Initialize.
	 */
	public static function init() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'possibly_add_manual_return_notice_script' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'possibly_add_import_return_notice_script' ) );
	}

	/**
	 * Adds a return to task list notice when completing the manual product task.
	 *
	 * @param string $hook Page hook.
	 */
	public static function possibly_add_manual_return_notice_script( $hook ) {
		$task = new Task( self::get_task() );

		if ( $task->is_complete || ! $task->is_active() ) {
			return;
		}

		global $post;
		if ( 'post.php' !== $hook || 'product' !== $post->post_type ) {
			return;
		}

		$script_assets_filename = Loader::get_script_asset_filename( 'wp-admin-scripts', 'onboarding-product-notice' );
		$script_assets          = require WC_ADMIN_ABSPATH . WC_ADMIN_DIST_JS_FOLDER . 'wp-admin-scripts/' . $script_assets_filename;

		wp_enqueue_script(
			'onboarding-product-notice',
			Loader::get_url( 'wp-admin-scripts/onboarding-product-notice', 'js' ),
			array_merge( array( WC_ADMIN_APP ), $script_assets ['dependencies'] ),
			WC_ADMIN_VERSION_NUMBER,
			true
		);
	}

	/**
	 * Adds a return to task list notice when completing the import product task.
	 *
	 * @param string $hook Page hook.
	 */
	public static function possibly_add_import_return_notice_script( $hook ) {
		$task = new Task( self::get_task() );
		$step = isset( $_GET['step'] ) ? $_GET['step'] : ''; // phpcs:ignore csrf ok, sanitization ok.

		if ( $task->is_complete || ! $task->is_active() ) {
			return;
		}

		if ( 'product_page_product_importer' !== $hook || 'done' !== $step ) {
			return;
		}

		$script_assets_filename = Loader::get_script_asset_filename( 'wp-admin-scripts', 'onboarding-product-import-notice' );
		$script_assets          = require WC_ADMIN_ABSPATH . WC_ADMIN_DIST_JS_FOLDER . 'wp-admin-scripts/' . $script_assets_filename;

		wp_enqueue_script(
			'onboarding-product-import-notice',
			Loader::get_url( 'wp-admin-scripts/onboarding-product-import-notice', 'js' ),
			array_merge( array( WC_ADMIN_APP ), $script_assets ['dependencies'] ),
			WC_ADMIN_VERSION_NUMBER,
			true
		);
	}

	/**
	 * Get the task arguments.
	 *
	 * @return array
	 */
	public static function get_task() {
		return array(
			'id'              => 'products',
			'title'           => __( 'Add my products', 'woocommerce-admin' ),
			'content'         => __(
				'Start by adding the first product to your store. You can add your products manually, via CSV, or import them from another service.',
				'woocommerce-admin'
			),
			'is_complete'     => self::has_products(),
			'can_view'        => true,
			'time'            => __( '1 minute per product', 'woocommerce-admin' ),
			'additional_data' => array(
				'has_products' => self::has_products(),
			),
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
