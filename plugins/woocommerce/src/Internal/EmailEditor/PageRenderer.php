<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\EmailEditor;

use Automattic\WooCommerce\EmailEditor\Engine\Settings_Controller;
use Automattic\WooCommerce\EmailEditor\Engine\Templates\Template;
use Automattic\WooCommerce\EmailEditor\Engine\Templates\Templates_Registry;
use Automattic\WooCommerce\EmailEditor\Engine\Theme_Controller;
use Automattic\WooCommerce\EmailEditor\Engine\User_Theme;
use Automattic\WooCommerce\EmailEditor\Email_Editor_Container;
use Automattic\WooCommerce\Internal\Admin\WCAdminAssets;

defined( 'ABSPATH' ) || exit;

/**
 * Class responsible for rendering the email editor page.
 */
class PageRenderer {
	/**
	 * Settings controller instance.
	 *
	 * @var Settings_Controller
	 */
	private Settings_Controller $settings_controller;

	/**
	 * Theme controller instance.
	 *
	 * @var Theme_Controller
	 */
	private Theme_Controller $theme_controller;

	/**
	 * User theme instance.
	 *
	 * @var User_Theme
	 */
	private User_Theme $user_theme;

	/**
	 * Template registry instance.
	 *
	 * @var Templates_Registry
	 */
	private Templates_Registry $template_registry;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$editor_container          = Email_Editor_Container::container();
		$this->settings_controller = $editor_container->get( Settings_Controller::class );
		$this->theme_controller    = $editor_container->get( Theme_Controller::class );
		$this->user_theme          = $editor_container->get( User_Theme::class );
		$this->template_registry   = $editor_container->get( Templates_Registry::class );
	}

	/**
	 * Render the email editor page.
	 */
	public function render() {
		$post_id     = isset( $_GET['post'] ) ? intval( $_GET['post'] ) : 0;  // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- We are not verifying the nonce here because we are not using the nonce in the function and the data is okay in this context (WP-admin errors out gracefully).
		$template_id = isset( $_GET['template'] ) ? sanitize_text_field( wp_unslash( $_GET['template'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- We are not verifying the nonce here because we are not using the nonce in the function and the data is okay in this context (WP-admin errors out gracefully).
		$post_type   = $template_id ? 'wp_template' : Integration::EMAIL_POST_TYPE;
		$post_id     = $template_id ? $template_id : $post_id;

		if ( ! $this->can_edit_post_in_email_editor( $post_id, $post_type ) ) {
			return;
		}

		// Load the email editor assets.
		$this->load_editor_assets( $post_id, $post_type );

		// Load CSS from Post Editor.
		wp_enqueue_style( 'wp-edit-post' );
		// Load CSS for the format library - used for example in popover.
		wp_enqueue_style( 'wp-format-library' );

		// Enqueue media library scripts.
		wp_enqueue_media();

		$this->preload_rest_api_data( $post_id, $post_type );

		require_once ABSPATH . 'wp-admin/admin-header.php';
		echo '<div id="woocommerce-email-editor" class="block-editor block-editor__container hide-if-no-js"></div>';
	}

	/**
	 * Load editor assets.
	 *
	 * @param int|string $post_id  The post ID.
	 * @param string     $post_type The post type.
	 */
	private function load_editor_assets( $post_id, string $post_type ): void {
		// Load the email editor integration script.
		// The JS file is located in plugins/woocommerce/client/admin/client/wp-admin-scripts/email-editor-integration/index.ts.
		WCAdminAssets::register_script( 'wp-admin-scripts', 'email-editor-integration', true );
		WCAdminAssets::register_style( 'email-editor-integration', 'style', true );

		$email_editor_assets_path = WC_ABSPATH . WC_ADMIN_DIST_JS_FOLDER . 'email-editor/';
		$email_editor_assets_url  = WC()->plugin_url() . '/' . WC_ADMIN_DIST_JS_FOLDER . 'email-editor/';

		// Email editor rich text JS - Because the Personalization Tags depend on Gutenberg 19.8.0 and higher
		// the following code replaces used Rich Text for the version containing the necessary changes.
		$rich_text_assets_params = require $email_editor_assets_path . 'assets/rich-text.asset.php';
		wp_deregister_script( 'wp-rich-text' );
		wp_enqueue_script(
			'wp-rich-text',
			$email_editor_assets_url . 'assets/rich-text.js',
			$rich_text_assets_params['dependencies'],
			$rich_text_assets_params['version'],
			true
		);
		// End of replacing Rich Text package.

		$file_name     = 'index';
		$assets_params = require $email_editor_assets_path . "{$file_name}.asset.php";

		wp_enqueue_script(
			'woocommerce_email_editor',
			$email_editor_assets_url . "{$file_name}.js",
			$assets_params['dependencies'],
			$assets_params['version'],
			true
		);
		wp_enqueue_style(
			'woocommerce_email_editor',
			$email_editor_assets_url . "style-{$file_name}.css",
			array(),
			$assets_params['version']
		);

		$current_user_email = wp_get_current_user()->user_email;

		// Fetch all email types from WooCommerce including those added by other plugins.
		$wc_emails   = \WC_Emails::instance();
		$email_types = $wc_emails->get_emails();
		$email_types = array_values(
			array_map(
				function ( $email ) {
					return array(
						'value' => $email->id,
						'label' => $email->title,
						'id'    => get_class( $email ),
					);
				},
				$email_types
			)
		);

		wp_localize_script(
			'woocommerce_email_editor',
			'WooCommerceEmailEditor',
			array(
				'current_post_type'     => esc_js( $post_type ),
				'current_post_id'       => $post_id,
				'current_wp_user_email' => esc_js( $current_user_email ),
				'editor_settings'       => $this->settings_controller->get_settings(),
				'editor_theme'          => $this->theme_controller->get_base_theme()->get_raw_data(),
				'user_theme_post_id'    => $this->user_theme->get_user_theme_post()->ID,
				'urls'                  => array(
					'listings' => admin_url( 'admin.php?page=wc-settings&tab=email' ),
					'send'     => admin_url( 'admin.php?page=wc-settings&tab=email' ),
				),
				'email_types'           => $email_types,
				'block_preview_url'     => esc_url( wp_nonce_url( admin_url( '?preview_woocommerce_mail_editor_content=true' ), 'preview-mail' ) ),
			)
		);
	}

	/**
	 * Preload REST API data for the email editor.
	 *
	 * @param int|string $post_id  The post ID.
	 * @param string     $post_type The post type.
	 */
	private function preload_rest_api_data( $post_id, string $post_type ): void {
		$email_post_type    = $post_type;
		$user_theme_post_id = $this->user_theme->get_user_theme_post()->ID;
		$template_slug      = get_post_meta( $post_id, '_wp_page_template', true );
		$routes             = array(
			"/wp/v2/{$email_post_type}/" . intval( $post_id ) . '?context=edit',
			"/wp/v2/types/{$email_post_type}?context=edit",
			'/wp/v2/global-styles/' . intval( $user_theme_post_id ) . '?context=view', // Global email styles.
			'/wp/v2/block-patterns/patterns',
			'/wp/v2/templates?context=view',
			'/wp/v2/block-patterns/categories',
			'/wp/v2/settings',
			'/wp/v2/types?context=view',
			'/wp/v2/taxonomies?context=view',
		);

		if ( $template_slug ) {
			$routes[] = '/wp/v2/templates/lookup?slug=' . $template_slug;
		} else {
			$routes[] = "/wp/v2/{$email_post_type}?context=edit&per_page=30&status=publish,sent";
		}

		// Preload the data for the specified routes.
		$preload_data = array_reduce(
			$routes,
			'rest_preload_api_request',
			array()
		);

		// Add inline script to set up preloading middleware.
		wp_add_inline_script(
			'wp-blocks',
			sprintf(
				'wp.apiFetch.use( wp.apiFetch.createPreloadingMiddleware( %s ) );',
				wp_json_encode( $preload_data )
			)
		);
	}

	/**
	 * Check if the post can be edited in the email editor.
	 *
	 * @param int|string $id   The post ID.
	 * @param string     $type The post type.
	 * @return bool True if the post can be edited, false otherwise.
	 */
	private function can_edit_post_in_email_editor( $id, string $type ): bool {
		// When we pass template we need to verify that the template is registered in the email template registry.
		if ( 'wp_template' === $type ) {
			$wp_template = get_block_template( $id );
			if ( ! $wp_template ) {
				return false;
			}
			$email_template = $this->template_registry->get_by_slug( $wp_template->slug );
			return $email_template instanceof Template;
		}

		// For post we need to verify that the post is of the email type.
		$post = get_post( $id );
		if ( $post instanceof \WP_Post && $type === $post->post_type ) {
			return true;
		}

		return false;
	}
}
