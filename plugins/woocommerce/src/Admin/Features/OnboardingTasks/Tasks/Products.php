<?php

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;
use Automattic\WooCommerce\Enums\ProductStatus;
use Automattic\WooCommerce\Internal\Admin\WCAdminAssets;
use Automattic\WooCommerce\Internal\Admin\Onboarding\OnboardingProfile;

/**
 * Products Task
 */
class Products extends Task {
	const HAS_PRODUCT_TRANSIENT = 'woocommerce_product_task_has_product_transient';

	/**
	 * Whether a deferred revert check has already been scheduled for this request.
	 *
	 * @var bool
	 */
	private static $revert_scheduled = false;

	/**
	 * Constructor
	 *
	 * @param TaskList $task_list Parent task list.
	 */
	public function __construct( $task_list ) {
		parent::__construct( $task_list );
		add_action( 'admin_enqueue_scripts', array( $this, 'possibly_add_import_return_notice_script' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'possibly_add_load_sample_return_notice_script' ) );

		add_action( 'woocommerce_update_product', array( $this, 'maybe_set_has_product_transient' ), 10, 2 );
		add_action( 'woocommerce_new_product', array( $this, 'maybe_set_has_product_transient' ), 10, 2 );
		add_action( 'untrashed_post', array( $this, 'maybe_set_has_product_transient_on_untrashed_post' ) );
		add_action( 'current_screen', array( $this, 'maybe_redirect_to_add_product_tasklist' ), 30, 0 );

		add_action( 'trashed_post', array( $this, 'on_product_trashed' ) );
		add_action( 'deleted_post_product', array( $this, 'on_product_deleted' ) );
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
		if ( $this->has_previously_completed() ) {
			return true;
		}

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
	 * If a task is always accessible, relevant for when a task list is hidden but a task can still be viewed.
	 *
	 * @return bool
	 */
	public function is_always_accessible() {
		return true;
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
	 * Set the has products transient if the post qualifies as a user created product.
	 *
	 * @param int $post_id Post ID.
	 */
	public function maybe_set_has_product_transient_on_untrashed_post( $post_id ) {
		if ( get_post_type( $post_id ) !== 'product' ) {
			return;
		}

		$this->maybe_set_has_product_transient( $post_id, wc_get_product( $post_id ) );
	}

	/**
	 * Set the has products transient if the product qualifies as a user created product.
	 *
	 * @param int        $product_id Product ID.
	 * @param WC_Product $product Product object.
	 */
	public function maybe_set_has_product_transient( $product_id, $product ) {
		if ( ! $this->has_previously_completed() && $this->is_valid_product( $product ) ) {
			set_transient( self::HAS_PRODUCT_TRANSIENT, 'yes' );
			$this->possibly_track_completion();
		}
	}

	/**
	 * Handle product trashing via the trashed_post hook.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function on_product_trashed( $post_id ) {
		if ( get_post_type( $post_id ) !== 'product' ) {
			return;
		}

		$this->revert_task_completion();
	}

	/**
	 * Handle permanent product deletion via the deleted_post_product hook.
	 *
	 * @return void
	 */
	public function on_product_deleted() {
		$this->revert_task_completion();
	}

	/**
	 * Schedule a deferred check to revert task completion if no products remain.
	 *
	 * Uses the shutdown hook so that bulk operations (e.g. trashing many products
	 * at once) only trigger a single has_products() query instead of one per product.
	 *
	 * @return void
	 */
	private function revert_task_completion(): void {
		delete_transient( self::HAS_PRODUCT_TRANSIENT );

		if ( self::$revert_scheduled ) {
			return;
		}

		self::$revert_scheduled = true;
		add_action( 'shutdown', array( $this, 'maybe_revert_on_shutdown' ) );
	}

	/**
	 * Re-check whether valid products still exist and revert task completion if none remain.
	 *
	 * Runs once at the end of the request via the shutdown hook.
	 *
	 * @return void
	 */
	public function maybe_revert_on_shutdown(): void {
		self::$revert_scheduled = false;

		if ( self::has_products() ) {
			return;
		}

		$completed_tasks = get_option( self::COMPLETED_OPTION, array() );
		$task_id         = $this->get_id();

		if ( in_array( $task_id, $completed_tasks, true ) ) {
			$completed_tasks = array_values( array_diff( $completed_tasks, array( $task_id ) ) );
			update_option( self::COMPLETED_OPTION, $completed_tasks );
		}
	}

	/**
	 * Check if the product qualifies as a user created product.
	 *
	 * @param WC_Product $product Product object.
	 * @return bool
	 */
	private function is_valid_product( $product ) {
		return ProductStatus::PUBLISH === $product->get_status() &&
			( ! $product->get_meta( '_headstart_post' ) ||
			get_post_meta( $product->get_id(), '_edit_last', true ) );
	}

	/**
	 * Check if the store has any user created published products.
	 *
	 * @return bool
	 */
	public static function has_products() {
		$product_exists = get_transient( self::HAS_PRODUCT_TRANSIENT );
		if ( $product_exists ) {
			return 'yes' === $product_exists;
		}

		global $wpdb;

		/*
		 * Check if any valid products exist and return 'yes' or 'no'
		 * A valid product must:
		 * 1. Be a published product post type
		 * 2. Meet one of these conditions:
		 *    - Have been edited by a user (_edit_last meta exists), OR
		 *    - Not have _headstart_post meta, OR
		 *    - Have _headstart_post meta but it's NULL
		 */
		$value = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT IF(
					EXISTS (
						SELECT 1 FROM {$wpdb->posts} p
						WHERE p.post_type = %s
						AND p.post_status = %s
						AND (
							EXISTS (
								SELECT 1 FROM {$wpdb->postmeta} pm
								WHERE pm.post_id = p.ID
								AND pm.meta_key = %s
							)
							OR
							NOT EXISTS (
								SELECT 1 FROM {$wpdb->postmeta} pm
								WHERE pm.post_id = p.ID
								AND pm.meta_key = %s
							)
							OR
							EXISTS (
								SELECT 1 FROM {$wpdb->postmeta} pm
								WHERE pm.post_id = p.ID
								AND pm.meta_key = %s
								AND pm.meta_value = ''
							)
						)
						LIMIT 1
					),
					'yes', 'no'
				)",
				'product',
				ProductStatus::PUBLISH,
				'_edit_last',
				'_headstart_post',
				'_headstart_post'
			)
		);

		set_transient( self::HAS_PRODUCT_TRANSIENT, $value );
		return 'yes' === $value;
	}

	/**
	 * Redirect to the add product tasklist if there are no products.
	 *
	 * @return void
	 */
	public function maybe_redirect_to_add_product_tasklist() {
		$screen = get_current_screen();
		if ( 'edit' === $screen->base && 'product' === $screen->post_type ) {
			// wp_count_posts is cached.
			$counts = (array) wp_count_posts( $screen->post_type );
			unset( $counts['auto-draft'] );
			$count = array_sum( $counts );
			if ( $count > 0 ) {
				return;
			}
			wp_safe_redirect( admin_url( 'admin.php?page=wc-admin&task=products' ) );
			exit;
		}
	}
}
