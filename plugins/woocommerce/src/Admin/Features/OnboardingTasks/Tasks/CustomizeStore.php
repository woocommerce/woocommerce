<?php

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;
use Jetpack_Gutenberg;

/**
 * Customize Your Store Task
 */
class CustomizeStore extends Task {
	/**
	 * Constructor
	 *
	 * @param TaskList $task_list Parent task list.
	 */
	public function __construct( $task_list ) {
		parent::__construct( $task_list );

		add_action( 'admin_enqueue_scripts', array( $this, 'possibly_add_site_editor_scripts' ) );
		add_action( 'after_switch_theme', array( $this, 'mark_task_as_complete' ) );
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
	 * Possibly add site editor scripts.
	 */
	public function possibly_add_site_editor_scripts() {
		$is_customize_store_pages = (
			isset( $_GET['page'] ) &&
			'wc-admin' === $_GET['page'] &&
			isset( $_GET['path'] ) &&
			str_starts_with( wc_clean( wp_unslash( $_GET['path'] ) ), '/customize-store' )
		);
		if ( ! $is_customize_store_pages ) {
			return;
		}

		// See: https://github.com/WordPress/WordPress/blob/master/wp-admin/site-editor.php.
		if ( ! wp_is_block_theme() ) {
			wp_die( esc_html__( 'The theme you are currently using is not compatible.', 'woocommerce' ) );
		}
		global $editor_styles;

		// Flag that we're loading the block editor.
		$current_screen = get_current_screen();
		$current_screen->is_block_editor( true );

		// Default to is-fullscreen-mode to avoid jumps in the UI.
		add_filter(
			'admin_body_class',
			static function( $classes ) {
				return "$classes is-fullscreen-mode";
			}
		);

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
	}

	/**
	 * Mark task as complete.
	 */
	public function mark_task_as_complete() {
		update_option( 'woocommerce_admin_customize_store_completed', 'yes' );
	}
}
