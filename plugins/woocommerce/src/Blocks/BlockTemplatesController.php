<?php
namespace Automattic\WooCommerce\Blocks;

use Automattic\WooCommerce\Blocks\Utils\BlockTemplateUtils;
use Automattic\WooCommerce\Blocks\Templates\ComingSoonTemplate;

/**
 * BlockTemplatesController class.
 *
 * @internal
 */
class BlockTemplatesController {

	/**
	 * Directory which contains all templates
	 *
	 * @var string
	 */
	const TEMPLATES_ROOT_DIR = 'templates';

	/**
	 * Initialization method.
	 */
	public function init() {
		add_filter( 'pre_get_block_template', array( $this, 'get_block_template_fallback' ), 10, 3 );
		add_filter( 'pre_get_block_file_template', array( $this, 'get_block_file_template' ), 10, 3 );
		add_filter( 'get_block_template', array( $this, 'add_block_template_details' ), 10, 3 );
		add_filter( 'get_block_templates', array( $this, 'add_block_templates' ), 10, 3 );
		add_filter( 'taxonomy_template_hierarchy', array( $this, 'add_fallback_template_to_hierarchy' ), 10, 1 );
		add_filter( 'block_type_metadata_settings', array( $this, 'add_plugin_templates_parts_support' ), 10, 2 );
		add_filter( 'block_type_metadata_settings', array( $this, 'prevent_shortcodes_html_breakage' ), 10, 2 );
		add_action( 'current_screen', array( $this, 'hide_template_selector_in_cart_checkout_pages' ), 10 );
	}

	/**
	 * Renders the `core/template-part` block on the server.
	 *
	 * This is done because the core handling for template parts only supports templates from the current theme, not
	 * from a plugin.
	 *
	 * @param array $attributes The block attributes.
	 * @return string The render.
	 */
	public function render_woocommerce_template_part( $attributes ) {
		if ( isset( $attributes['theme'] ) && 'woocommerce/woocommerce' === $attributes['theme'] ) {
			$template_part = get_block_template( $attributes['theme'] . '//' . $attributes['slug'], 'wp_template_part' );

			if ( $template_part && ! empty( $template_part->content ) ) {
				$content = do_blocks( $template_part->content );

				if ( empty( $attributes['tagName'] ) || tag_escape( $attributes['tagName'] ) !== $attributes['tagName'] ) {
					$html_tag = 'div';
				} else {
					$html_tag = esc_attr( $attributes['tagName'] );
				}
				$wrapper_attributes = get_block_wrapper_attributes();

				return "<$html_tag $wrapper_attributes>" . str_replace( ']]>', ']]&gt;', $content ) . "</$html_tag>";
			}
		}
		return function_exists( '\gutenberg_render_block_core_template_part' ) ? \gutenberg_render_block_core_template_part( $attributes ) : \render_block_core_template_part( $attributes );
	}

	/**
	 * This function is used on the `pre_get_block_template` hook to return the fallback template from the db in case
	 * the template is eligible for it.
	 *
	 * Currently, the Products by Category, Products by Tag and Products by Attribute templates fall back to the
	 * Product Catalog template. That means that if there are customizations in the Product Catalog template,
	 * they are also reflected in the other templates as long as they haven't been customized as well.
	 *
	 * @param \WP_Block_Template|null $template Block template object to short-circuit the default query,
	 *                                          or null to allow WP to run its normal queries.
	 * @param string                  $id Template unique identifier (example: theme_slug//template_slug).
	 * @param string                  $template_type wp_template or wp_template_part.
	 *
	 * @return object|null
	 */
	public function get_block_template_fallback( $template, $id, $template_type ) {
		// Add protection against invalid ids.
		if ( ! is_string( $id ) || ! strstr( $id, '//' ) ) {
			return null;
		}
		// Add protection against invalid template types.
		if (
			'wp_template' !== $template_type &&
			'wp_template_part' !== $template_type
		) {
			return null;
		}
		$template_name_parts = explode( '//', $id );
		$theme               = $template_name_parts[0] ?? '';
		$slug                = $template_name_parts[1] ?? '';
		$registered_template = BlockTemplateUtils::get_template( $slug );

		if ( empty( $theme ) || empty( $slug ) || ! $registered_template || ! isset( $registered_template->fallback_template ) ) {
			return null;
		}

		$wp_query_args  = array(
			'post_name__in' => array( $registered_template->fallback_template, $slug ),
			'post_type'     => $template_type,
			'post_status'   => array( 'auto-draft', 'draft', 'publish', 'trash' ),
			'no_found_rows' => true,
			'tax_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => 'wp_theme',
					'field'    => 'name',
					'terms'    => $theme,
				),
			),
		);
		$template_query = new \WP_Query( $wp_query_args );
		$posts          = $template_query->posts;

		// If we have more than one result from the query, it means that the current template is present in the db (has
		// been customized by the user) and we should not return the fallback template.
		if ( count( $posts ) > 1 ) {
			return null;
		}

		if ( count( $posts ) > 0 && $registered_template->fallback_template === $posts[0]->post_name ) {
			$template = _build_block_template_result_from_post( $posts[0] );

			if ( ! is_wp_error( $template ) ) {
				$template->id          = $theme . '//' . $slug;
				$template->slug        = $slug;
				$template->title       = BlockTemplateUtils::get_block_template_title( $slug );
				$template->description = BlockTemplateUtils::get_block_template_description( $slug );
				unset( $template->source );

				return $template;
			}
		}

		return $template;
	}

	/**
	 * Adds the fallback template to the template hierarchy.
	 *
	 * @param array $template_hierarchy A list of template candidates, in descending order of priority.
	 */
	public function add_fallback_template_to_hierarchy( $template_hierarchy ) {
		$template_slugs = array_map(
			'_strip_template_file_suffix',
			$template_hierarchy
		);

		foreach ( $template_slugs as $template_slug ) {
			$template = BlockTemplateUtils::get_template( $template_slug );
			if ( $template && isset( $template->fallback_template ) ) {
				$template_hierarchy[] = $template->fallback_template;
			}
		}

		return $template_hierarchy;
	}

	/**
	 * By default, the Template Part Block only supports template parts that are in the current theme directory.
	 * This render_callback wrapper allows us to add support for plugin-housed template parts.
	 *
	 * @param array $settings Array of determined settings for registering a block type.
	 * @param array $metadata     Metadata provided for registering a block type.
	 */
	public function add_plugin_templates_parts_support( $settings, $metadata ) {
		if (
			isset( $metadata['name'], $settings['render_callback'] ) &&
			'core/template-part' === $metadata['name'] &&
			in_array( $settings['render_callback'], array( 'render_block_core_template_part', 'gutenberg_render_block_core_template_part' ), true )
		) {
			$settings['render_callback'] = array( $this, 'render_woocommerce_template_part' );
		}
		return $settings;
	}


	/**
	 * Prevents shortcodes in templates having their HTML content broken by wpautop.
	 *
	 * @see https://core.trac.wordpress.org/ticket/58366 for more info.
	 *
	 * @param array $settings Array of determined settings for registering a block type.
	 * @param array $metadata     Metadata provided for registering a block type.
	 */
	public function prevent_shortcodes_html_breakage( $settings, $metadata ) {
		if (
				isset( $metadata['name'], $settings['render_callback'] ) &&
				'core/shortcode' === $metadata['name']
			) {
			$settings['original_render_callback'] = $settings['render_callback'];
			$settings['render_callback']          = function ( $attributes, $content ) use ( $settings ) {
				// The shortcode has already been rendered, so look for the cart/checkout HTML.
				if ( strstr( $content, 'woocommerce-cart-form' ) || strstr( $content, 'wc-empty-cart-message' ) || strstr( $content, 'woocommerce-checkout-form' ) ) {
					// Return early before wpautop runs again.
					return $content;
				}

				$render_callback = $settings['original_render_callback'];

				return $render_callback( $attributes, $content );
			};
		}
		return $settings;
	}

	/**
	 * Prevents the pages that are assigned as Cart/Checkout from showing the "template" selector in the page-editor.
	 * We want to avoid this flow and point users towards the Site Editor instead.
	 *
	 * @return void
	 */
	public function hide_template_selector_in_cart_checkout_pages() {
		if ( ! is_admin() ) {
			return;
		}

		$current_screen = get_current_screen();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $current_screen && 'page' === $current_screen->id && ! empty( $_GET['post'] ) && in_array( absint( $_GET['post'] ), array( wc_get_page_id( 'cart' ), wc_get_page_id( 'checkout' ) ), true ) ) {
			wp_add_inline_style( 'wc-blocks-editor-style', '.edit-post-post-template { display: none; }' );
		}
	}

	/**
	 * This function checks if there's a block template file in `woocommerce/templates/templates/`
	 * to return to pre_get_posts short-circuiting the query in Gutenberg.
	 *
	 * @param \WP_Block_Template|null $template Return a block template object to short-circuit the default query,
	 *                                               or null to allow WP to run its normal queries.
	 * @param string                  $id Template unique identifier (example: theme_slug//template_slug).
	 * @param string                  $template_type wp_template or wp_template_part.
	 *
	 * @return mixed|\WP_Block_Template|\WP_Error
	 */
	public function get_block_file_template( $template, $id, $template_type ) {
		$template_name_parts = explode( '//', $id );

		if ( count( $template_name_parts ) < 2 ) {
			return $template;
		}

		list( $template_id, $template_slug ) = $template_name_parts;

		// If the template is not present in the theme but its fallback template is,
		// let's use the theme's fallback template.
		if ( BlockTemplateUtils::template_is_eligible_for_fallback_from_theme( $template_slug ) ) {
			$registered_template = BlockTemplateUtils::get_template( $template_slug );
			$template_path       = BlockTemplateUtils::get_theme_template_path( $registered_template->fallback_template );
			$template_object     = BlockTemplateUtils::create_new_block_template_object( $template_path, $template_type, $template_slug, true );
			return BlockTemplateUtils::build_template_result_from_file( $template_object, $template_type );
		}

		// This is a real edge-case, we are supporting users who have saved templates under the deprecated slug. See its definition for more information.
		// You can likely ignore this code unless you're supporting/debugging early customised templates.
		if ( BlockTemplateUtils::DEPRECATED_PLUGIN_SLUG === strtolower( $template_id ) ) {
			// Because we are using get_block_templates we have to unhook this method to prevent a recursive loop where this filter is applied.
			remove_filter( 'pre_get_block_file_template', array( $this, 'get_block_file_template' ), 10, 3 );
			$template_with_deprecated_id = get_block_template( $id, $template_type );
			// Let's hook this method back now that we have used the function.
			add_filter( 'pre_get_block_file_template', array( $this, 'get_block_file_template' ), 10, 3 );

			if ( null !== $template_with_deprecated_id ) {
				return $template_with_deprecated_id;
			}
		}

		// If we are not dealing with a WooCommerce template let's return early and let it continue through the process.
		if ( BlockTemplateUtils::PLUGIN_SLUG !== $template_id ) {
			return $template;
		}

		// If we don't have a template let Gutenberg do its thing.
		if ( ! $this->block_template_is_available( $template_slug, $template_type ) ) {
			return $template;
		}

		$directory          = BlockTemplateUtils::get_templates_directory( $template_type );
		$template_file_path = $directory . '/' . $template_slug . '.html';
		$template_object    = BlockTemplateUtils::create_new_block_template_object( $template_file_path, $template_type, $template_slug );
		$template_built     = BlockTemplateUtils::build_template_result_from_file( $template_object, $template_type );

		if ( null !== $template_built ) {
			return $template_built;
		}

		// Hand back over to Gutenberg if we can't find a template.
		return $template;
	}

	/**
	 * Add the template title and description to WooCommerce templates.
	 *
	 * @param WP_Block_Template|null $block_template The found block template, or null if there isn't one.
	 * @param string                 $id             Template unique identifier (example: 'theme_slug//template_slug').
	 * @param array                  $template_type  Template type: 'wp_template' or 'wp_template_part'.
	 * @return WP_Block_Template|null
	 */
	public function add_block_template_details( $block_template, $id, $template_type ) {
		return BlockTemplateUtils::update_template_data( $block_template, $template_type );
	}

	/**
	 * Add the block template objects to be used.
	 *
	 * @param array  $query_result Array of template objects.
	 * @param array  $query Optional. Arguments to retrieve templates.
	 * @param string $template_type wp_template or wp_template_part.
	 * @return array
	 */
	public function add_block_templates( $query_result, $query, $template_type ) {
		$slugs = isset( $query['slug__in'] ) ? $query['slug__in'] : array();

		if ( ! BlockTemplateUtils::supports_block_templates( $template_type ) && ! in_array( ComingSoonTemplate::SLUG, $slugs, true ) ) {
			return $query_result;
		}

		$post_type      = isset( $query['post_type'] ) ? $query['post_type'] : '';
		$template_files = $this->get_block_templates( $slugs, $template_type );
		$theme_slug     = wp_get_theme()->get_stylesheet();

		// @todo: Add apply_filters to _gutenberg_get_template_files() in Gutenberg to prevent duplication of logic.
		foreach ( $template_files as $template_file ) {

			// If we have a template which is eligible for a fallback, we need to explicitly tell Gutenberg that
			// it has a theme file (because it is using the fallback template file). And then `continue` to avoid
			// adding duplicates.
			if ( BlockTemplateUtils::set_has_theme_file_if_fallback_is_available( $query_result, $template_file ) ) {
				continue;
			}

			// If the current $post_type is set (e.g. on an Edit Post screen), and isn't included in the available post_types
			// on the template file, then lets skip it so that it doesn't get added. This is typically used to hide templates
			// in the template dropdown on the Edit Post page.
			if ( $post_type &&
				isset( $template_file->post_types ) &&
				! in_array( $post_type, $template_file->post_types, true )
			) {
				continue;
			}

			// It would be custom if the template was modified in the editor, so if it's not custom we can load it from
			// the filesystem.
			if ( 'custom' === $template_file->source ) {
				$query_result[] = $template_file;
				continue;
			}

			$possible_template_ids = [
				$theme_slug . '//' . $template_file->slug,
				$theme_slug . '//' . BlockTemplateUtils::DIRECTORY_NAMES['TEMPLATE_PARTS'] . '/' . $template_file->slug,
				$theme_slug . '//' . BlockTemplateUtils::DIRECTORY_NAMES['DEPRECATED_TEMPLATE_PARTS'] . '/' . $template_file->slug,
			];

			$is_custom                 = false;
			$query_result_template_ids = array_column( $query_result, 'id' );

			foreach ( $possible_template_ids as $template_id ) {
				if ( in_array( $template_id, $query_result_template_ids, true ) ) {
					$is_custom = true;
					break;
				}
			}
			$fits_slug_query =
				! isset( $query['slug__in'] ) || in_array( $template_file->slug, $query['slug__in'], true );
			$fits_area_query =
				! isset( $query['area'] ) || ( property_exists( $template_file, 'area' ) && $template_file->area === $query['area'] );
			$should_include  = ! $is_custom && $fits_slug_query && $fits_area_query;
			if ( $should_include ) {
				$template       = BlockTemplateUtils::build_template_result_from_file( $template_file, $template_type );
				$query_result[] = $template;
			}
		}

		// We need to remove theme (i.e. filesystem) templates that have the same slug as a customised one.
		// This only affects saved templates that were saved BEFORE a theme template with the same slug was added.
		$query_result = BlockTemplateUtils::remove_theme_templates_with_custom_alternative( $query_result );

		// There is the chance that the user customized the default template, installed a theme with a custom template
		// and customized that one as well. When that happens, duplicates might appear in the list.
		// See: https://github.com/woocommerce/woocommerce/issues/42220.
		$query_result = BlockTemplateUtils::remove_duplicate_customized_templates( $query_result, $theme_slug );

		/**
		 * WC templates from theme aren't included in `$this->get_block_templates()` but are handled by Gutenberg.
		 * We need to do additional search through all templates file to update title and description for WC
		 * templates that aren't listed in theme.json.
		 */
		$query_result = array_map(
			function ( $template ) use ( $template_type ) {
				return BlockTemplateUtils::update_template_data( $template, $template_type );
			},
			$query_result
		);

		return $query_result;
	}

	/**
	 * Gets the templates saved in the database.
	 *
	 * @param array  $slugs An array of slugs to retrieve templates for.
	 * @param string $template_type wp_template or wp_template_part.
	 *
	 * @return int[]|\WP_Post[] An array of found templates.
	 */
	public function get_block_templates_from_db( $slugs = array(), $template_type = 'wp_template' ) {
		wc_deprecated_function( 'BlockTemplatesController::get_block_templates_from_db()', '7.8', '\Automattic\WooCommerce\Blocks\Utils\BlockTemplateUtils::get_block_templates_from_db()' );
		return BlockTemplateUtils::get_block_templates_from_db( $slugs, $template_type );
	}

	/**
	 * Gets the templates from the WooCommerce blocks directory, skipping those for which a template already exists
	 * in the theme directory.
	 *
	 * @param string[] $slugs An array of slugs to filter templates by. Templates whose slug does not match will not be returned.
	 * @param array    $already_found_templates Templates that have already been found, these are customised templates that are loaded from the database.
	 * @param string   $template_type wp_template or wp_template_part.
	 *
	 * @return array Templates from the WooCommerce blocks plugin directory.
	 */
	public function get_block_templates_from_woocommerce( $slugs, $already_found_templates, $template_type = 'wp_template' ) {
		$template_files = BlockTemplateUtils::get_template_paths( $template_type );
		$templates      = array();

		foreach ( $template_files as $template_file ) {
			// Skip the template if it's blockified, and we should only use classic ones.
			if ( ! BlockTemplateUtils::should_use_blockified_product_grid_templates() && strpos( $template_file, 'blockified' ) !== false ) {
				continue;
			}

			$template_slug = BlockTemplateUtils::generate_template_slug_from_path( $template_file );

			// This template does not have a slug we're looking for. Skip it.
			if ( is_array( $slugs ) && count( $slugs ) > 0 && ! in_array( $template_slug, $slugs, true ) ) {
				continue;
			}

			// If the theme already has a template, or the template is already in the list (i.e. it came from the
			// database) then we should not overwrite it with the one from the filesystem.
			if (
				BlockTemplateUtils::theme_has_template( $template_slug ) ||
				count(
					array_filter(
						$already_found_templates,
						function ( $template ) use ( $template_slug ) {
							$template_obj = (object) $template; //phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.Found
							return $template_obj->slug === $template_slug;
						}
					)
				) > 0 ) {
				continue;
			}

			if ( BlockTemplateUtils::template_is_eligible_for_fallback_from_db( $template_slug, $already_found_templates ) ) {
				$template              = clone BlockTemplateUtils::get_fallback_template_from_db( $template_slug, $already_found_templates );
				$template_id           = explode( '//', $template->id );
				$template->id          = $template_id[0] . '//' . $template_slug;
				$template->slug        = $template_slug;
				$template->title       = BlockTemplateUtils::get_block_template_title( $template_slug );
				$template->description = BlockTemplateUtils::get_block_template_description( $template_slug );
				$templates[]           = $template;
				continue;
			}

			// If the template is not present in the theme but its fallback template is,
			// let's use the theme's fallback template.
			if ( BlockTemplateUtils::template_is_eligible_for_fallback_from_theme( $template_slug ) ) {
				$registered_template = BlockTemplateUtils::get_template( $template_slug );
				$template_file       = BlockTemplateUtils::get_theme_template_path( $registered_template->fallback_template );
				$templates[]         = BlockTemplateUtils::create_new_block_template_object( $template_file, $template_type, $template_slug, true );
				continue;
			}

			// At this point the template only exists in the Blocks filesystem and has not been saved in the DB,
			// or superseded by the theme.
			$templates[] = BlockTemplateUtils::create_new_block_template_object( $template_file, $template_type, $template_slug );
		}

		return $templates;
	}

	/**
	 * Get and build the block template objects from the block template files.
	 *
	 * @param array  $slugs An array of slugs to retrieve templates for.
	 * @param string $template_type wp_template or wp_template_part.
	 *
	 * @return array WP_Block_Template[] An array of block template objects.
	 */
	public function get_block_templates( $slugs = array(), $template_type = 'wp_template' ) {
		$templates_from_db  = BlockTemplateUtils::get_block_templates_from_db( $slugs, $template_type );
		$templates_from_woo = $this->get_block_templates_from_woocommerce( $slugs, $templates_from_db, $template_type );

		return array_merge( $templates_from_db, $templates_from_woo );
	}

	/**
	 * Checks whether a block template with that name exists in Woo Blocks
	 *
	 * @param string $template_name Template to check.
	 * @param array  $template_type wp_template or wp_template_part.
	 *
	 * @return boolean
	 */
	public function block_template_is_available( $template_name, $template_type = 'wp_template' ) {
		if ( ! $template_name ) {
			return false;
		}
		$directory = BlockTemplateUtils::get_templates_directory( $template_type ) . '/' . $template_name . '.html';

		return is_readable(
			$directory
		) || $this->get_block_templates( array( $template_name ), $template_type );
	}
}
