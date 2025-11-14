<?php

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;
use WP_Post;

/**
 * Customize Your Store Task
 *
 * @internal
 */
class CustomizeStore extends Task {
	/**
	 * Constructor
	 *
	 * @param TaskList $task_list Parent task list.
	 */
	public function __construct( $task_list ) {
		parent::__construct( $task_list );

		add_action( 'save_post_wp_global_styles', array( $this, 'mark_task_as_complete_block_theme' ), 10, 3 );
		add_action( 'save_post_wp_template', array( $this, 'mark_task_as_complete_block_theme' ), 10, 3 );
		add_action( 'save_post_wp_template_part', array( $this, 'mark_task_as_complete_block_theme' ), 10, 3 );
		add_action( 'customize_save_after', array( $this, 'mark_task_as_complete_classic_theme' ) );

		// Handle splash page actions.
		add_action( 'admin_init', array( $this, 'handle_splash_page_actions' ) );
		add_action( 'admin_menu', array( $this, 'register_splash_page' ) );
	}

	/**
	 * Mark the task as complete whenever the user updates their global styles.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post Post object.
	 * @param bool    $update Whether this is an existing post being updated.
	 *
	 * @return void
	 */
	public function mark_task_as_complete_block_theme( $post_id, $post, $update ) {
		if ( $post instanceof WP_Post ) {
			$is_complete = $this->has_custom_global_styles( $post ) || $this->has_custom_template( $post );

			if ( $is_complete ) {
				update_option( 'woocommerce_admin_customize_store_completed', 'yes' );
			}
		}
	}

	/**
	 * Mark the task as complete whenever the user saves the customizer changes.
	 *
	 * @return void
	 */
	public function mark_task_as_complete_classic_theme() {
		update_option( 'woocommerce_admin_customize_store_completed', 'yes' );
	}

	/**
	 * ID.
	 *
	 * @return string
	 */
	public function get_id() {
		return 'customize-store';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Customize your store ', 'woocommerce' );
	}

	/**
	 * Content.
	 *
	 * @return string
	 */
	public function get_content() {
		return '';
	}

	/**
	 * Time.
	 *
	 * @return string
	 */
	public function get_time() {
		return '';
	}

	/**
	 * Task completion.
	 *
	 * @return bool
	 */
	public function is_complete() {
		return get_option( 'woocommerce_admin_customize_store_completed' ) === 'yes';
	}

	/**
	 * Task visibility.
	 *
	 * @return bool
	 */
	public function can_view() {
		return true;
	}

	/**
	 * Action URL.
	 *
	 * @return string
	 */
	public function get_action_url() {
		return admin_url( 'admin.php?page=wc-customize-store' );
	}

	/**
	 * Handle splash page button actions.
	 *
	 * @return void
	 */
	public function handle_splash_page_actions() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['page'] ) || 'wc-customize-store' !== $_GET['page'] ) {
			return;
		}

		// Handle button actions - mark task as complete and redirect.
		if ( isset( $_GET['action'] ) ) {
			$action = sanitize_text_field( wp_unslash( $_GET['action'] ) );

			// Mark task as complete when either button is clicked.
			update_option( 'woocommerce_admin_customize_store_completed', 'yes' );

			if ( 'design' === $action ) {
				// Redirect to site editor.
				if ( wp_is_block_theme() ) {
					wp_safe_redirect( admin_url( 'site-editor.php' ) );
				} else {
					wp_safe_redirect( admin_url( 'customize.php' ) );
				}
				exit;
			} elseif ( 'marketplace' === $action ) {
				// Redirect to marketplace/themes.
				$marketplace_url = $this->get_marketplace_url();
				wp_safe_redirect( $marketplace_url );
				exit;
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Get marketplace URL for themes.
	 *
	 * @return string
	 */
	private function get_marketplace_url() {
		return admin_url( 'admin.php?page=wc-admin&tab=themes&path=%2Fextensions' );
	}

	/**
	 * Register the splash page in admin menu.
	 *
	 * @return void
	 */
	public function register_splash_page() {
		add_submenu_page(
			null, // Hidden from menu.
			__( 'Customize your store', 'woocommerce' ),
			__( 'Customize your store', 'woocommerce' ),
			'manage_woocommerce',
			'wc-customize-store',
			array( $this, 'render_splash_page' )
		);
	}

	/**
	 * Render the customize store splash page.
	 *
	 * @return void
	 */
	public function render_splash_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'woocommerce' ) );
		}

		$design_url   = add_query_arg( 'action', 'design', admin_url( 'admin.php?page=wc-customize-store' ) );
		$marketplace_url = add_query_arg( 'action', 'marketplace', admin_url( 'admin.php?page=wc-customize-store' ) );

		include __DIR__ . '/views/html-customize-store-splash.php';
	}

	/**
	 * Checks if the post has custom global styles stored (if it is different from the default global styles).
	 *
	 * @param WP_Post $post The post object.
	 * @return bool
	 */
	private function has_custom_global_styles( WP_Post $post ) {
		$required_keys = array( 'version', 'isGlobalStylesUserThemeJSON' );

		$json_post_content = json_decode( $post->post_content, true );
		if ( is_null( $json_post_content ) ) {
			return false;
		}

		$post_content_keys = array_keys( $json_post_content );

		return ! empty( array_diff( $post_content_keys, $required_keys ) ) || ! empty( array_diff( $required_keys, $post_content_keys ) );
	}

	/**
	 * Checks if the post is a template or a template part.
	 *
	 * @param WP_Post $post The post object.
	 * @return bool Whether the post is a template or a template part.
	 */
	private function has_custom_template( WP_Post $post ) {
		return in_array( $post->post_type, array( 'wp_template', 'wp_template_part' ), true );
	}
}
