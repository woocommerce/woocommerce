<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types=1);
namespace Automattic\WooCommerce\EmailEditor\Engine;

use Automattic\WooCommerce\EmailEditor\Engine\Logger\Email_Editor_Logger;

/**
 * Class responsible for managing email editor assets.
 */
class Assets_Manager {
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
	 * Email editor assets path.
	 *
	 * @var string
	 */
	private string $assets_path = '';

	/**
	 * Email editor assets URL.
	 *
	 * @var string
	 */
	private string $assets_url = '';

	/**
	 * Logger instance.
	 *
	 * @var Email_Editor_Logger
	 */
	private Email_Editor_Logger $logger;

	/**
	 * Assets Manager constructor with all dependencies.
	 *
	 * @param Settings_Controller $settings_controller Settings controller instance.
	 * @param Theme_Controller    $theme_controller Theme controller instance.
	 * @param User_Theme          $user_theme User theme instance.
	 * @param Email_Editor_Logger $logger Email editor logger instance.
	 */
	public function __construct(
		Settings_Controller $settings_controller,
		Theme_Controller $theme_controller,
		User_Theme $user_theme,
		Email_Editor_Logger $logger
	) {
		$this->settings_controller = $settings_controller;
		$this->theme_controller    = $theme_controller;
		$this->user_theme          = $user_theme;
		$this->logger              = $logger;
	}

	/**
	 * Sets the path for the email editor assets.
	 *
	 * @param string $assets_path The path to the email editor assets directory.
	 * @return void
	 */
	public function set_assets_path( string $assets_path ): void {
		$this->assets_path = $assets_path;
	}

	/**
	 *  Sets the URL for the email editor assets.
	 *
	 * @param string $assets_url The URL to the email editor assets directory.
	 * @return void
	 */
	public function set_assets_url( string $assets_url ): void {
		$this->assets_url = $assets_url;
	}

	/**
	 * Initialize the assets manager.
	 */
	public function initialize(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
	}

	/**
	 * Enqueue admin styles that are needed by the email editor.
	 */
	public function enqueue_admin_styles(): void {
		// Calling action that loads registered blockTypes.
		do_action( 'enqueue_block_editor_assets' );

		// Load CSS from Post Editor.
		wp_enqueue_style( 'wp-edit-post' );
		// Load CSS for the format library - used for example in popover.
		wp_enqueue_style( 'wp-format-library' );
		// Enqueue CSS containing --wp--preset variables.
		wp_enqueue_global_styles_css_custom_properties();

		// Enqueue media library scripts.
		wp_enqueue_media();
	}

	/**
	 * Render the email editor's required HTML and admin header.
	 *
	 * @param string $element_id Optional. The ID of the main container element. Default is 'woocommerce-email-editor'.
	 */
	public function render_email_editor_html( string $element_id = 'woocommerce-email-editor' ): void {
		// @phpstan-ignore-next-line -- PHPStan tried to check if the file exists.
		require_once ABSPATH . 'wp-admin/admin-header.php';
		echo '<div id="' . esc_attr( $element_id ) . '" class="block-editor block-editor__container hide-if-no-js"></div>';
	}

	/**
	 * Load editor assets.
	 *
	 * @param \WP_Post|\WP_Block_Template $edited_item The edited post or template.
	 * @param string                      $script_name The name of the registered script.
	 */
	public function load_editor_assets( $edited_item, string $script_name ): void {
		$post_type = $edited_item instanceof \WP_Post ? $edited_item->post_type : 'wp_template';
		$post_id   = $edited_item instanceof \WP_Post ? $edited_item->ID : $edited_item->id;

		$email_editor_assets_path = rtrim( $this->assets_path, '/' ) . '/';
		$email_editor_assets_url  = rtrim( $this->assets_url, '/' ) . '/';

		// Email editor rich text JS - Because the Personalization Tags depend on Gutenberg 19.8.0 and higher
		// the following code replaces used Rich Text for the version containing the necessary changes.
		$rich_text_assets_file = $email_editor_assets_path . 'assets/rich-text.asset.php';
		if ( ! file_exists( $rich_text_assets_file ) ) {
			$this->logger->error( 'Rich Text assets file does not exist.', array( 'path' => $rich_text_assets_file ) );
		} else {
			$rich_text_assets = require $rich_text_assets_file;
			wp_deregister_script( 'wp-rich-text' );
			wp_enqueue_script(
				'wp-rich-text',
				$email_editor_assets_url . 'assets/rich-text.js',
				$rich_text_assets['dependencies'],
				$rich_text_assets['version'],
				true
			);
		}
		// End of replacing Rich Text package.

		$assets_file = $email_editor_assets_path . 'style.asset.php';
		if ( ! file_exists( $assets_file ) ) {
			$this->logger->error( 'Email editor assets file does not exist.', array( 'path' => $assets_file ) );
		} else {
			$assets_file = require $assets_file;
			wp_enqueue_style(
				'wc-admin-email-editor-integration',
				$email_editor_assets_url . 'style.css',
				array(),
				$assets_file['version']
			);
		}

		// The get_block_categories() function expects a WP_Post or WP_Block_Editor_Context object.
		// Therefore, we need to create an instance of WP_Block_Editor_Context when $edited_item is an instance of WP_Block_Template.
		if ( $edited_item instanceof \WP_Block_Template ) {
			$context = new \WP_Block_Editor_Context(
				array(
					'post' => $edited_item,
				)
			);
		} else {
			$context = $edited_item;
		}
		// The email editor needs to load block categories to avoid warning and missing category names.
		// See: https://github.com/WordPress/WordPress/blob/753817d462955eb4e40a89034b7b7c375a1e43f3/wp-admin/edit-form-blocks.php#L116-L120.
		wp_add_inline_script(
			'wp-blocks',
			sprintf( 'wp.blocks.setCategories( %s );', wp_json_encode( get_block_categories( $context ), JSON_HEX_TAG | JSON_UNESCAPED_SLASHES ) ),
			'after'
		);

		// Preload server-registered block schemas to avoid warning about missing block titles.
		// See: https://github.com/WordPress/WordPress/blob/753817d462955eb4e40a89034b7b7c375a1e43f3/wp-admin/edit-form-blocks.php#L144C1-L148C3.
		wp_add_inline_script(
			'wp-blocks',
			sprintf( 'wp.blocks.unstable__bootstrapServerSideBlockDefinitions( %s );', wp_json_encode( get_block_editor_server_block_settings(), JSON_HEX_TAG | JSON_UNESCAPED_SLASHES ) )
		);

		$localization_data = array(
			'current_post_type'     => $post_type,
			'current_post_id'       => $post_id,
			'current_wp_user_email' => wp_get_current_user()->user_email,
			'editor_settings'       => $this->settings_controller->get_settings(),
			'editor_theme'          => $this->theme_controller->get_base_theme()->get_raw_data(),
			'user_theme_post_id'    => $this->user_theme->get_user_theme_post()->ID,
			'urls'                  => array(
				'listings'     => admin_url( 'admin.php?page=wc-settings&tab=email' ),
				'send'         => admin_url( 'admin.php?page=wc-settings&tab=email' ),
				'back'         => admin_url( 'admin.php?page=wc-settings&tab=email' ),
				'createCoupon' => admin_url( 'post-new.php?post_type=shop_coupon' ),
			),
		);

		wp_localize_script(
			$script_name,
			'WooCommerceEmailEditor',
			apply_filters( 'woocommerce_email_editor_script_localization_data', $localization_data )
		);

		$this->preload_rest_api_data( $post_id, $post_type );
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
		$template_slug      = get_post_meta( (int) $post_id, '_wp_page_template', true );
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

		if ( is_string( $template_slug ) ) {
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
				wp_json_encode( $preload_data, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES )
			)
		);
	}
}
