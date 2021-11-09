<?php

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Admin\Loader;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks\Products;

/**
 * Appearance Task
 */
class Appearance {
	/**
	 * Initialize.
	 */
	public static function init() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'add_media_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'possibly_add_return_notice_script' ) );
	}

	/**
	 * Get the task arguments.
	 *
	 * @return array
	 */
	public static function get_task() {
		return array(
			'id'              => 'appearance',
			'title'           => __( 'Personalize my store', 'woocommerce-admin' ),
			'content'         => __(
				'Add your logo, create a homepage, and start designing your store.',
				'woocommerce-admin'
			),
			'action_label'    => __( "Let's go", 'woocommerce-admin' ),
			'is_complete'     => Task::is_task_actioned( 'appearance' ),
			'can_view'        => true,
			'time'            => __( '2 minutes', 'woocommerce-admin' ),
			'additional_data' => array(
				'has_homepage' => self::has_homepage(),
				'has_products' => Products::has_products(),
				'stylesheet'   => get_option( 'stylesheet' ),
				'theme_mods'   => get_theme_mods(),
			),
		);
	}

	/**
	 * Add media scripts for image uploader.
	 */
	public static function add_media_scripts() {
		$task = new Task( self::get_task() );

		if ( ! $task->can_view ) {
			return;
		}

		wp_enqueue_media();
	}


	/**
	 * Adds a return to task list notice when completing the task.
	 *
	 * @param string $hook Page hook.
	 */
	public static function possibly_add_return_notice_script( $hook ) {
		global $post;
		$task = new Task( self::get_task() );

		if ( $task->is_complete || ! $task->is_active() ) {
			return;
		}

		if ( 'post.php' !== $hook || 'page' !== $post->post_type ) {
			return;
		}

		$script_assets_filename = Loader::get_script_asset_filename( 'wp-admin-scripts', 'onboarding-homepage-notice' );
		$script_assets          = require WC_ADMIN_ABSPATH . WC_ADMIN_DIST_JS_FOLDER . 'wp-admin-scripts/' . $script_assets_filename;

		wp_enqueue_script(
			'onboarding-homepage-notice',
			Loader::get_url( 'wp-admin-scripts/onboarding-homepage-notice', 'js' ),
			array_merge( array( WC_ADMIN_APP ), $script_assets ['dependencies'] ),
			WC_ADMIN_VERSION_NUMBER,
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
