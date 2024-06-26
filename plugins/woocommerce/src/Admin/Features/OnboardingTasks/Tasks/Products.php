<?php

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;
use Automattic\WooCommerce\Internal\Admin\WCAdminAssets;
use Automattic\WooCommerce\Internal\Admin\Onboarding\OnboardingProfile;

/**
 * Products Task
 */
class Products extends Task {
	const PRODUCT_COUNT_TRANSIENT_NAME = 'woocommerce_product_task_product_count_transient';

	/**
	 * Constructor
	 *
	 * @param TaskList $task_list Parent task list.
	 */
	public function __construct( $task_list ) {
		parent::__construct( $task_list );
		add_action( 'admin_enqueue_scripts', array( $this, 'possibly_add_manual_return_notice_script' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'possibly_add_import_return_notice_script' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'possibly_add_load_sample_return_notice_script' ) );

		add_action( 'woocommerce_update_product', array( $this, 'delete_product_count_cache' ) );
		add_action( 'woocommerce_new_product', array( $this, 'delete_product_count_cache' ) );
		add_action( 'wp_trash_post', array( $this, 'delete_product_count_cache' ) );
		add_action( 'untrashed_post', array( $this, 'delete_product_count_cache' ) );
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
		$onboarding_profile = get_option( OnboardingProfile::DATA_OPTION, array() );

		if ( isset( $onboarding_profile['business_choice'] ) && 'im_already_selling' === $onboarding_profile['business_choice'] ) {
			return __( 'Import your products', 'woocommerce' );
		}

		return __( 'Add your products', 'woocommerce' );
	}

	/**
	 * Content.
	 *
	 * @return string
	 */
	public function get_content() {
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
	 * Additional data.
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
		if ( $hook !== 'post.php' || $post->post_type !== 'product' ) {
			return;
		}

		if ( ! $this->is_active() || ! $this->is_complete() ) {
			return;
		}

		WCAdminAssets::register_script( 'wp-admin-scripts', 'onboarding-product-notice', true );

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

		if ( $hook !== 'product_page_product_importer' || $step !== 'done' ) {
			return;
		}

		if ( ! $this->is_active() || $this->is_complete() ) {
			return;
		}

		WCAdminAssets::register_script( 'wp-admin-scripts', 'onboarding-product-import-notice', true );
	}

	/**
	 * Adds a return to task list notice when completing the loading sample products action.
	 *
	 * @param string $hook Page hook.
	 */
	public function possibly_add_load_sample_return_notice_script( $hook ) {
		if ( $hook !== 'edit.php' || get_query_var( 'post_type' ) !== 'product' ) {
			return;
		}

		$referer = wp_get_referer();
		if ( ! $referer || strpos( $referer, wc_admin_url() ) !== 0 ) {
			return;
		}

		if ( ! isset( $_GET[ Task::ACTIVE_TASK_TRANSIENT ] ) ) {
			return;
		}

		$task_id = sanitize_title_with_dashes( wp_unslash( $_GET[ Task::ACTIVE_TASK_TRANSIENT ] ) );
		if ( $task_id !== $this->get_id() || ! $this->is_complete() ) {
			return;
		}

		WCAdminAssets::register_script( 'wp-admin-scripts', 'onboarding-load-sample-products-notice', true );
	}

	/**
	 * Delete the product count transient used in has_products() method to refresh the cache.
	 *
	 * @return void
	 */
	public static function delete_product_count_cache() {
		delete_transient( self::PRODUCT_COUNT_TRANSIENT_NAME );
	}

	/**
	 * Check if the store has any user created published products.
	 *
	 * @return bool
	 */
	public static function has_products() {
		$product_counts = get_transient( self::PRODUCT_COUNT_TRANSIENT_NAME );
		if ( false !== $product_counts && is_numeric( $product_counts ) ) {
			return (int) $product_counts > 0;
		}

		$product_counts = self::count_user_products();
		set_transient( self::PRODUCT_COUNT_TRANSIENT_NAME, $product_counts );
		return $product_counts > 0;
	}

	/**
	 * Count the number of user created products.
	 * Generated products have the _headstart_post meta key.
	 *
	 * @return int The number of user created products.
	 */
	private static function count_user_products() {
		$args = array(
			'post_type'   => 'product',
			'post_status' => 'publish',
			'fields'      => 'ids',
			'meta_query'  => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'relation' => 'OR',
				array(
					'key'     => '_headstart_post',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => '_edit_last',
					'compare' => 'EXISTS',
				),
			),
		);

		$products_query = new \WP_Query( $args );

		return $products_query->found_posts;
	}
}
