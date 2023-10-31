<?php
/**
 * REST API Onboarding Tasks Controller
 *
 * Handles requests to complete various onboarding tasks.
 */

namespace Automattic\WooCommerce\Admin\API;

use Automattic\WooCommerce\Internal\Admin\Onboarding\OnboardingIndustries;
use Automattic\WooCommerce\Internal\Admin\Onboarding\OnboardingProfile;
use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\TaskLists;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\DeprecatedExtendedTask;

defined( 'ABSPATH' ) || exit;

/**
 * Customize Your Store Controller.
 *
 * @internal
 * @extends WC_REST_Data_Controller
 */
class CustomizeYourStore extends \WC_REST_Data_Controller {
	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc-admin';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'cys';

	/**
	 * Register routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/block-editor-settings',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_block_editor_settings' ),
					'permission_callback' => array( $this, 'can_access_block_editor_settings' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Check if the current user has access to block editor settings
	 */
	public function can_access_block_editor_settings() {
		return true;
	}

	public function get_block_editor_settings() {
		global $wp_scripts;
		global $wp_styles;
		// @todo -- should not use hard coded wp-admin; relace with constant
		if (!function_exists('get_block_editor_server_block_settings')) {
			require_once ABSPATH . 'wp-admin/includes/post.php';
		}

		global $editor_styles;

		$block_editor_context   = new \WP_Block_Editor_Context( array( 'name' => 'core/edit-site' ) );
		$indexed_template_types = array();
		foreach ( get_default_block_template_types() as $slug => $template_type ) {
			$template_type['slug']    = (string) $slug;
			$indexed_template_types[] = $template_type;
		}

		$custom_settings = array(
			'siteUrl'                   => site_url(),
			'postsPerPage'              => get_option( 'posts_per_page' ),
			'styles'                    => get_block_editor_theme_styles(),
			'defaultTemplateTypes'      => $indexed_template_types,
			'defaultTemplatePartAreas'  => get_allowed_block_template_part_areas(),
			'supportsLayout'            => wp_theme_has_theme_json(),
			'supportsTemplatePartsMode' => ! wp_is_block_theme() && current_theme_supports( 'block-template-parts' ),
		);

		// Add additional back-compat patterns registered by `current_screen` et al.
		$custom_settings['__experimentalAdditionalBlockPatterns']          = \WP_Block_Patterns_Registry::get_instance()->get_all_registered( true );
		$custom_settings['__experimentalAdditionalBlockPatternCategories'] = \WP_Block_Pattern_Categories_Registry::get_instance()->get_all_registered( true );

		$editor_settings         = get_block_editor_settings( $custom_settings, $block_editor_context );
		$active_global_styles_id = \WP_Theme_JSON_Resolver::get_user_global_styles_post_id();
		$active_theme            = get_stylesheet();
		$preload_paths           = array(
			array( '/wp/v2/media', 'OPTIONS' ),
			'/wp/v2/types?context=view',
			'/wp/v2/types/wp_template?context=edit',
			'/wp/v2/types/wp_template-part?context=edit',
			'/wp/v2/templates?context=edit&per_page=-1',
			'/wp/v2/template-parts?context=edit&per_page=-1',
			'/wp/v2/themes?context=edit&status=active',
			'/wp/v2/global-styles/' . $active_global_styles_id . '?context=edit',
			'/wp/v2/global-styles/' . $active_global_styles_id,
			'/wp/v2/global-styles/themes/' . $active_theme,
		);

		block_editor_rest_api_preload( $preload_paths, $block_editor_context );

		wp_add_inline_script(
			'wp-blocks',
			sprintf(
				'window.wcBlockSettings = %s;',
				wp_json_encode( $editor_settings )
			)
		);

		// Preload server-registered block schemas.
		wp_add_inline_script(
			'wp-blocks',
			'wp.blocks.unstable__bootstrapServerSideBlockDefinitions(' . wp_json_encode( get_block_editor_server_block_settings() ) . ');'
		);

		wp_add_inline_script(
			'wp-blocks',
			sprintf( 'wp.blocks.setCategories( %s );', wp_json_encode( isset( $editor_settings['blockCategories'] ) ? $editor_settings['blockCategories'] : array() ) ),
			'after'
		);

		wp_enqueue_script( 'wp-editor' );
		wp_enqueue_script( 'wp-format-library' ); // Not sure if this is needed.
		wp_enqueue_script( 'wp-router' );

		wp_enqueue_style( 'wp-editor' );
		wp_enqueue_style( 'wp-edit-site' );
		wp_enqueue_style( 'wp-format-library' );
		wp_enqueue_media();

		if (
			current_theme_supports( 'wp-block-styles' ) &&
			( ! is_array( $editor_styles ) || count( $editor_styles ) === 0 )
		) {
			wp_enqueue_style( 'wp-block-library-theme' );
		}
		/** This action is documented in wp-admin/edit-form-blocks.php
		 *
		 * @since 8.0.3
		 */
		do_action( 'enqueue_block_editor_assets' );

		// Load Jetpack's block editor assets because they are not enqueued by default.
		if ( class_exists( 'Jetpack_Gutenberg' ) ) {
			Jetpack_Gutenberg::enqueue_block_editor_assets();
		}

		ob_start();
		wp_print_scripts(array('wp-blocks', 'wp-editor', 'wp-format-library', 'wp-router'));
		wp_print_styles(array('wp-blocks', 'wp-editor', 'wp-format-library', 'wp-router', 'wp-block-library-theme'));
		$output = ob_get_contents();
		ob_end_clean();
		return rest_ensure_response($output);

	}
}
