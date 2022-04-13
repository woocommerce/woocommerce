<?php

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Admin\PageController;
use Automattic\WooCommerce\Internal\Admin\Loader;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks\Products;
use Automattic\WooCommerce\Internal\Admin\WCAdminAssets;

/**
 * Appearance Task
 */
class Appearance extends Task {

	/**
	 * Constructor
	 *
	 * @param TaskList $task_list Parent task list.
	 */
	public function __construct( $task_list ) {
		parent::__construct( $task_list );
		add_action( 'admin_enqueue_scripts', array( $this, 'add_media_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'possibly_add_return_notice_script' ) );
	}

	/**
	 * ID.
	 *
	 * @return string
	 */
	public function get_id() {
		return 'appearance';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		if ( count( $this->task_list->get_sections() ) > 0 && ! $this->is_complete() ) {
			return __( 'Make your store stand out with unique design', 'woocommerce' );
		}
		if ( true === $this->get_parent_option( 'use_completed_title' ) ) {
			if ( $this->is_complete() ) {
				return __( 'You personalized your store', 'woocommerce' );
			}
			return __( 'Personalize your store', 'woocommerce' );
		}
		return __( 'Personalize my store', 'woocommerce' );
	}

	/**
	 * Content.
	 *
	 * @return string
	 */
	public function get_content() {
		if ( count( $this->task_list->get_sections() ) > 0 ) {
			return __( 'Upload your logo to adapt the store to your brand’s personality.', 'woocommerce' );
		}
		return __(
			'Add your logo, create a homepage, and start designing your store.',
			'woocommerce'
		);
	}

	/**
	 * Time.
	 *
	 * @return string
	 */
	public function get_time() {
		return __( '2 minutes', 'woocommerce' );
	}

	/**
	 * Addtional data.
	 *
	 * @return array
	 */
	public function get_additional_data() {
		return array(
			'has_homepage' => self::has_homepage(),
			'has_products' => Products::has_products(),
			'stylesheet'   => get_option( 'stylesheet' ),
			'theme_mods'   => get_theme_mods(),
		);
	}

	/**
	 * Add media scripts for image uploader.
	 */
	public function add_media_scripts() {
		if ( ! PageController::is_admin_page() || ! $this->can_view() ) {
			return;
		}

		wp_enqueue_media();
	}


	/**
	 * Adds a return to task list notice when completing the task.
	 *
	 * @param string $hook Page hook.
	 */
	public function possibly_add_return_notice_script( $hook ) {
		global $post;

		if ( 'post.php' !== $hook || 'page' !== $post->post_type ) {
			return;
		}

		if ( $this->is_complete() || ! $this->is_active() ) {
			return;
		}

		$script_assets_filename = WCAdminAssets::get_script_asset_filename( 'wp-admin-scripts', 'onboarding-homepage-notice' );
		$script_assets          = require WC_ADMIN_ABSPATH . WC_ADMIN_DIST_JS_FOLDER . 'wp-admin-scripts/' . $script_assets_filename;

		wp_enqueue_script(
			'onboarding-homepage-notice',
			WCAdminAssets::get_url( 'wp-admin-scripts/onboarding-homepage-notice', 'js' ),
			array_merge( array( WC_ADMIN_APP ), $script_assets ['dependencies'] ),
			WC_VERSION,
			true
		);
	}

	/**
	 * Check if the site has a homepage set up.
	 */
	public static function has_homepage() {
		if ( 'classic' === get_option( 'classic-editor-replace' ) ) {
			return true;
		}

		$homepage_id = get_option( 'woocommerce_onboarding_homepage_post_id', false );

		if ( ! $homepage_id ) {
			return false;
		}

		$post      = get_post( $homepage_id );
		$completed = $post && 'publish' === $post->post_status;

		return $completed;
	}
}
