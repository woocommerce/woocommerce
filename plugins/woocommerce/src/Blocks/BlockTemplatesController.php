<?php
namespace Automattic\WooCommerce\Blocks;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Blocks\Domain\Package;
use Automattic\WooCommerce\Blocks\Templates\CartTemplate;
use Automattic\WooCommerce\Blocks\Templates\CheckoutTemplate;
use Automattic\WooCommerce\Blocks\Templates\ProductAttributeTemplate;
use Automattic\WooCommerce\Blocks\Templates\ProductSearchResultsTemplate;
use Automattic\WooCommerce\Blocks\Templates\SingleProductTemplateCompatibility;
use Automattic\WooCommerce\Blocks\Utils\BlockTemplateUtils;
use Automattic\WooCommerce\Blocks\Templates\OrderConfirmationTemplate;
use Automattic\WooCommerce\Blocks\Templates\SingleProductTemplate;

/**
 * BlockTypesController class.
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
	 * Package instance.
	 *
	 * @var Package
	 */
	private $package;

	/**
	 * Constructor.
	 *
	 * @param Package $package An instance of Package.
	 */
	public function __construct( Package $package ) {
		$this->package = $package;

		$feature_gating                                 = $package->feature();
		$is_block_templates_controller_refactor_enabled = $feature_gating->is_block_templates_controller_refactor_enabled();

		// This feature is gated for WooCommerce versions 6.0.0 and above.
		if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '6.0.0', '>=' ) && ! $is_block_templates_controller_refactor_enabled ) {
			$this->init();
		}
	}

	/**
	 * Initialization method.
	 */
	protected function init() {
		add_filter( 'default_wp_template_part_areas', array( $this, 'register_mini_cart_template_part_area' ), 10, 1 );
		add_action( 'template_redirect', array( $this, 'render_block_template' ) );
		add_filter( 'pre_get_block_template', array( $this, 'get_block_template_fallback' ), 10, 3 );
		add_filter( 'pre_get_block_file_template', array( $this, 'get_block_file_template' ), 10, 3 );
		add_filter( 'get_block_templates', array( $this, 'add_block_templates' ), 10, 3 );
		add_filter( 'current_theme_supports-block-templates', array( $this, 'remove_block_template_support_for_shop_page' ) );
		add_filter( 'taxonomy_template_hierarchy', array( $this, 'add_archive_product_to_eligible_for_fallback_templates' ), 10, 1 );
		add_filter( 'post_type_archive_title', array( $this, 'update_product_archive_title' ), 10, 2 );
		add_action( 'after_switch_theme', array( $this, 'check_should_use_blockified_product_grid_templates' ), 10, 2 );

		if ( wc_current_theme_is_fse_theme() ) {
			// By default, the Template Part Block only supports template parts that are in the current theme directory.
			// This render_callback wrapper allows us to add support for plugin-housed template parts.
			add_filter(
				'block_type_metadata_settings',
				function( $settings, $metadata ) {
					if (
						isset( $metadata['name'], $settings['render_callback'] ) &&
						'core/template-part' === $metadata['name'] &&
						in_array( $settings['render_callback'], [ 'render_block_core_template_part', 'gutenberg_render_block_core_template_part' ], true )
					) {
						$settings['render_callback'] = [ $this, 'render_woocommerce_template_part' ];
					}
					return $settings;
				},
				10,
				2
			);

			// Prevents shortcodes in templates having their HTML content broken by wpautop.
			// @see https://core.trac.wordpress.org/ticket/58366 for more info.
			add_filter(
				'block_type_metadata_settings',
				function( $settings, $metadata ) {
					if (
						isset( $metadata['name'], $settings['render_callback'] ) &&
						'core/shortcode' === $metadata['name']
					) {
						$settings['original_render_callback'] = $settings['render_callback'];
						$settings['render_callback']          = function( $attributes, $content ) use ( $settings ) {
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
				},
				10,
				2
			);

			/**
			 * Prevents the pages that are assigned as cart/checkout from showing the "template" selector in the page-editor.
			 * We want to avoid this flow and point users towards the site editor instead.
			 */
			add_action(
				'current_screen',
				function () {
					if ( ! is_admin() ) {
						return;
					}

					$current_screen = get_current_screen();

					// phpcs:ignore WordPress.Security.NonceVerification.Recommended
					if ( $current_screen && 'page' === $current_screen->id && ! empty( $_GET['post'] ) && in_array( absint( $_GET['post'] ), [ wc_get_page_id( 'cart' ), wc_get_page_id( 'checkout' ) ], true ) ) {
						wp_add_inline_style( 'wc-blocks-editor-style', '.edit-post-post-template { display: none; }' );
					}
				},
				10
			);
		}
	}

	/**
	 * Add Mini-Cart to the default template part areas.
	 *
	 * @param array $default_area_definitions An array of supported area objects.
	 * @return array The supported template part areas including the Mini-Cart one.
	 */
	public function register_mini_cart_template_part_area( $default_area_definitions ) {
		$mini_cart_template_part_area = [
			'area'        => 'mini-cart',
			'label'       => __( 'Mini-Cart', 'woocommerce' ),
			'description' => __( 'The Mini-Cart template allows shoppers to see their cart items and provides access to the Cart and Checkout pages.', 'woocommerce' ),
			'icon'        => 'mini-cart',
			'area_tag'    => 'mini-cart',
		];
		return array_merge( $default_area_definitions, [ $mini_cart_template_part_area ] );
	}

	/**
	 * Renders the `core/template-part` block on the server.
	 *
	 * @param array $attributes The block attributes.
	 * @return string The render.
	 */
	public function render_woocommerce_template_part( $attributes ) {
		if ( isset( $attributes['theme'] ) && 'woocommerce/woocommerce' === $attributes['theme'] ) {
			$template_part = BlockTemplateUtils::get_block_template( $attributes['theme'] . '//' . $attributes['slug'], 'wp_template_part' );

			if ( $template_part && ! empty( $template_part->content ) ) {
				return do_blocks( $template_part->content );
			}
		}
		return function_exists( '\gutenberg_render_block_core_template_part' ) ? \gutenberg_render_block_core_template_part( $attributes ) : \render_block_core_template_part( $attributes );
	}

	/**
	 * This function is used on the `pre_get_block_template` hook to return the fallback template from the db in case
	 * the template is eligible for it.
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

		if ( empty( $theme ) || empty( $slug ) || ! BlockTemplateUtils::template_is_eligible_for_product_archive_fallback( $slug ) ) {
			return null;
		}

		$wp_query_args  = array(
			'post_name__in' => array( 'archive-product', $slug ),
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
		// been customized by the user) and we should not return the `archive-product` template.
		if ( count( $posts ) > 1 ) {
			return null;
		}

		if ( count( $posts ) > 0 && 'archive-product' === $posts[0]->post_name ) {
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
	 * Adds the `archive-product` template to the `taxonomy-product_cat`, `taxonomy-product_tag`, `taxonomy-attribute`
	 * templates to be able to fall back to it.
	 *
	 * @param array $template_hierarchy A list of template candidates, in descending order of priority.
	 */
	public function add_archive_product_to_eligible_for_fallback_templates( $template_hierarchy ) {
		$template_slugs = array_map(
			'_strip_template_file_suffix',
			$template_hierarchy
		);

		$templates_eligible_for_fallback = array_filter(
			$template_slugs,
			array( BlockTemplateUtils::class, 'template_is_eligible_for_product_archive_fallback' )
		);

		if ( count( $templates_eligible_for_fallback ) > 0 ) {
			$template_hierarchy[] = 'archive-product';
		}

		return $template_hierarchy;
	}

	/**
	 * Checks the old and current themes and determines if the "wc_blocks_use_blockified_product_grid_block_as_template"
	 * option need to be updated accordingly.
	 *
	 * @param string    $old_name Old theme name.
	 * @param \WP_Theme $old_theme Instance of the old theme.
	 * @return void
	 */
	public function check_should_use_blockified_product_grid_templates( $old_name, $old_theme ) {
		if ( ! wc_current_theme_is_fse_theme() ) {
			update_option( Options::WC_BLOCK_USE_BLOCKIFIED_PRODUCT_GRID_BLOCK_AS_TEMPLATE, wc_bool_to_string( false ) );
			return;
		}

		if ( ! $old_theme->is_block_theme() && wc_current_theme_is_fse_theme() ) {
			update_option( Options::WC_BLOCK_USE_BLOCKIFIED_PRODUCT_GRID_BLOCK_AS_TEMPLATE, wc_bool_to_string( true ) );
			return;
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

		// If the theme has an archive-product.html template, but not a taxonomy-product_cat/tag/attribute.html template let's use the themes archive-product.html template.
		if ( BlockTemplateUtils::template_is_eligible_for_product_archive_fallback_from_theme( $template_slug ) ) {
			$template_path   = BlockTemplateUtils::get_theme_template_path( 'archive-product' );
			$template_object = BlockTemplateUtils::create_new_block_template_object( $template_path, $template_type, $template_slug, true );
			return BlockTemplateUtils::build_template_result_from_file( $template_object, $template_type );
		}

		// This is a real edge-case, we are supporting users who have saved templates under the deprecated slug. See its definition for more information.
		// You can likely ignore this code unless you're supporting/debugging early customised templates.
		if ( BlockTemplateUtils::DEPRECATED_PLUGIN_SLUG === strtolower( $template_id ) ) {
			// Because we are using get_block_templates we have to unhook this method to prevent a recursive loop where this filter is applied.
			remove_filter( 'pre_get_block_file_template', array( $this, 'get_block_file_template' ), 10, 3 );
			$template_with_deprecated_id = BlockTemplateUtils::get_block_template( $id, $template_type );
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
	 * Add the block template objects to be used.
	 *
	 * @param array  $query_result Array of template objects.
	 * @param array  $query Optional. Arguments to retrieve templates.
	 * @param string $template_type wp_template or wp_template_part.
	 * @return array
	 */
	public function add_block_templates( $query_result, $query, $template_type ) {
		if ( ! BlockTemplateUtils::supports_block_templates( $template_type ) ) {
			return $query_result;
		}

		$post_type      = isset( $query['post_type'] ) ? $query['post_type'] : '';
		$slugs          = isset( $query['slug__in'] ) ? $query['slug__in'] : array();
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
			if ( 'custom' !== $template_file->source ) {
				$template = BlockTemplateUtils::build_template_result_from_file( $template_file, $template_type );
			} else {
				$template_file->title       = BlockTemplateUtils::get_block_template_title( $template_file->slug );
				$template_file->description = BlockTemplateUtils::get_block_template_description( $template_file->slug );
				$query_result[]             = $template_file;
				continue;
			}

			$is_not_custom   = false === array_search(
				$theme_slug . '//' . $template_file->slug,
				array_column( $query_result, 'id' ),
				true
			);
			$fits_slug_query =
				! isset( $query['slug__in'] ) || in_array( $template_file->slug, $query['slug__in'], true );
			$fits_area_query =
				! isset( $query['area'] ) || ( property_exists( $template_file, 'area' ) && $template_file->area === $query['area'] );
			$should_include  = $is_not_custom && $fits_slug_query && $fits_area_query;
			if ( $should_include ) {
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
			function( $template ) {
				if ( str_contains( $template->slug, 'single-product' ) ) {
					// We don't want to add the compatibility layer on the Editor Side.
					// The second condition is necessary to not apply the compatibility layer on the REST API. Gutenberg uses the REST API to clone the template.
					// More details: https://github.com/woocommerce/woocommerce-blocks/issues/9662.
					if ( ( ! is_admin() && ! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) && ! BlockTemplateUtils::template_has_legacy_template_block( $template ) ) {
						// Add the product class to the body. We should move this to a more appropriate place.
						add_filter(
							'body_class',
							function( $classes ) {
								return array_merge( $classes, wc_get_product_class() );
							}
						);

						global $product;

						if ( ! $product instanceof \WC_Product ) {
							$product_id = get_the_ID();
							if ( $product_id ) {
								wc_setup_product_data( $product_id );
							}
						}

						if ( post_password_required() ) {
							$template->content = SingleProductTemplate::add_password_form( $template->content );
						} else {
							$template->content = SingleProductTemplateCompatibility::add_compatibility_layer( $template->content );
						}
					}
				}

				if ( 'theme' === $template->origin && BlockTemplateUtils::template_has_title( $template ) ) {
					return $template;
				}
				if ( $template->title === $template->slug ) {
					$template->title = BlockTemplateUtils::get_block_template_title( $template->slug );
				}
				if ( ! $template->description ) {
					$template->description = BlockTemplateUtils::get_block_template_description( $template->slug );
				}
				return $template;
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
		$directory      = BlockTemplateUtils::get_templates_directory( $template_type );
		$template_files = BlockTemplateUtils::get_template_paths( $directory );
		$templates      = array();

		foreach ( $template_files as $template_file ) {
			// Skip the Product Gallery template part, as it is not supposed to be exposed at this point.
			if ( str_contains( $template_file, 'templates/parts/product-gallery.html' ) ) {
				continue;
			}

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

			if ( BlockTemplateUtils::template_is_eligible_for_product_archive_fallback_from_db( $template_slug, $already_found_templates ) ) {
				$template              = clone BlockTemplateUtils::get_fallback_template_from_db( $template_slug, $already_found_templates );
				$template_id           = explode( '//', $template->id );
				$template->id          = $template_id[0] . '//' . $template_slug;
				$template->slug        = $template_slug;
				$template->title       = BlockTemplateUtils::get_block_template_title( $template_slug );
				$template->description = BlockTemplateUtils::get_block_template_description( $template_slug );
				$templates[]           = $template;
				continue;
			}

			// If the theme has an archive-product.html template, but not a taxonomy-product_cat/tag/attribute.html template let's use the themes archive-product.html template.
			if ( BlockTemplateUtils::template_is_eligible_for_product_archive_fallback_from_theme( $template_slug ) ) {
				$template_file = BlockTemplateUtils::get_theme_template_path( 'archive-product' );
				$templates[]   = BlockTemplateUtils::create_new_block_template_object( $template_file, $template_type, $template_slug, true );
				continue;
			}

			// At this point the template only exists in the Blocks filesystem, if is a taxonomy-product_cat/tag/attribute.html template
			// let's use the archive-product.html template from Blocks.
			if ( BlockTemplateUtils::template_is_eligible_for_product_archive_fallback( $template_slug ) ) {
				$template_file = $this->get_template_path_from_woocommerce( 'archive-product' );
				$templates[]   = BlockTemplateUtils::create_new_block_template_object( $template_file, $template_type, $template_slug, false );
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
		$templates          = array_merge( $templates_from_db, $templates_from_woo );

		return BlockTemplateUtils::filter_block_templates_by_feature_flag( $templates );
	}

	/**
	 * Returns the path of a template on the Blocks template folder.
	 *
	 * @param string $template_slug Block template slug e.g. single-product.
	 * @param string $template_type wp_template or wp_template_part.
	 *
	 * @return string
	 */
	public function get_template_path_from_woocommerce( $template_slug, $template_type = 'wp_template' ) {
		return BlockTemplateUtils::get_templates_directory( $template_type ) . '/' . $template_slug . '.html';
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

	/**
	 * Renders the default block template from Woo Blocks if no theme templates exist.
	 */
	public function render_block_template() {
		if ( is_embed() || ! BlockTemplateUtils::supports_block_templates() ) {
			return;
		}

		if (
			is_singular( 'product' ) && $this->block_template_is_available( 'single-product' )
		) {
			global $post;

			$valid_slugs = [ 'single-product' ];
			if ( 'product' === $post->post_type && $post->post_name ) {
				$valid_slugs[] = 'single-product-' . $post->post_name;
			}
			$templates = get_block_templates( array( 'slug__in' => $valid_slugs ) );

			if ( isset( $templates[0] ) && BlockTemplateUtils::template_has_legacy_template_block( $templates[0] ) ) {
				add_filter( 'woocommerce_disable_compatibility_layer', '__return_true' );
			}

			if ( ! BlockTemplateUtils::theme_has_template( 'single-product' ) ) {
				add_filter( 'woocommerce_has_block_template', '__return_true', 10, 0 );
			}
		} elseif (
			( is_product_taxonomy() && is_tax( 'product_cat' ) ) && $this->block_template_is_available( 'taxonomy-product_cat' )
		) {
			$templates = get_block_templates( array( 'slug__in' => array( 'taxonomy-product_cat' ) ) );

			if ( isset( $templates[0] ) && BlockTemplateUtils::template_has_legacy_template_block( $templates[0] ) ) {
				add_filter( 'woocommerce_disable_compatibility_layer', '__return_true' );
			}

			if ( ! BlockTemplateUtils::theme_has_template( 'taxonomy-product_cat' ) ) {
				add_filter( 'woocommerce_has_block_template', '__return_true', 10, 0 );
			}
		} elseif (
			( is_product_taxonomy() && is_tax( 'product_tag' ) ) && $this->block_template_is_available( 'taxonomy-product_tag' )
		) {
			$templates = get_block_templates( array( 'slug__in' => array( 'taxonomy-product_tag' ) ) );

			if ( isset( $templates[0] ) && BlockTemplateUtils::template_has_legacy_template_block( $templates[0] ) ) {
				add_filter( 'woocommerce_disable_compatibility_layer', '__return_true' );
			}

			if ( ! BlockTemplateUtils::theme_has_template( 'taxonomy-product_tag' ) ) {
				add_filter( 'woocommerce_has_block_template', '__return_true', 10, 0 );
			}
		} elseif ( is_post_type_archive( 'product' ) && is_search() ) {
			$templates = get_block_templates( array( 'slug__in' => array( ProductSearchResultsTemplate::SLUG ) ) );

			if ( isset( $templates[0] ) && BlockTemplateUtils::template_has_legacy_template_block( $templates[0] ) ) {
				add_filter( 'woocommerce_disable_compatibility_layer', '__return_true' );
			}

			if ( ! BlockTemplateUtils::theme_has_template( ProductSearchResultsTemplate::SLUG ) ) {
				add_filter( 'woocommerce_has_block_template', '__return_true', 10, 0 );
			}
		} elseif (
			( is_post_type_archive( 'product' ) || is_page( wc_get_page_id( 'shop' ) ) ) && $this->block_template_is_available( 'archive-product' )
		) {
			$templates = get_block_templates( array( 'slug__in' => array( 'archive-product' ) ) );

			if ( isset( $templates[0] ) && BlockTemplateUtils::template_has_legacy_template_block( $templates[0] ) ) {
				add_filter( 'woocommerce_disable_compatibility_layer', '__return_true' );
			}

			if ( ! BlockTemplateUtils::theme_has_template( 'archive-product' ) ) {
				add_filter( 'woocommerce_has_block_template', '__return_true', 10, 0 );
			}
		} elseif (
			is_cart() &&
			! BlockTemplateUtils::theme_has_template( CartTemplate::get_slug() ) && $this->block_template_is_available( CartTemplate::get_slug() )
		) {
			add_filter( 'woocommerce_has_block_template', '__return_true', 10, 0 );
		} elseif (
			is_checkout() &&
			! BlockTemplateUtils::theme_has_template( CheckoutTemplate::get_slug() ) && $this->block_template_is_available( CheckoutTemplate::get_slug() )
		) {
			add_filter( 'woocommerce_has_block_template', '__return_true', 10, 0 );
		} else {
			$queried_object = get_queried_object();
			if ( is_null( $queried_object ) ) {
				return;
			}

			if ( isset( $queried_object->taxonomy ) && taxonomy_is_product_attribute( $queried_object->taxonomy ) && $this->block_template_is_available( ProductAttributeTemplate::SLUG )
			) {
				$templates = get_block_templates( array( 'slug__in' => array( ProductAttributeTemplate::SLUG ) ) );

				if ( isset( $templates[0] ) && BlockTemplateUtils::template_has_legacy_template_block( $templates[0] ) ) {
					add_filter( 'woocommerce_disable_compatibility_layer', '__return_true' );
				}

				if ( ! BlockTemplateUtils::theme_has_template( ProductAttributeTemplate::SLUG ) ) {
					add_filter( 'woocommerce_has_block_template', '__return_true', 10, 0 );
				}
			}
		}
	}

	/**
	 * Remove the template panel from the Sidebar of the Shop page because
	 * the Site Editor handles it.
	 *
	 * @see https://github.com/woocommerce/woocommerce-gutenberg-products-block/issues/6278
	 *
	 * @param bool $is_support Whether the active theme supports block templates.
	 *
	 * @return bool
	 */
	public function remove_block_template_support_for_shop_page( $is_support ) {
		global $pagenow, $post;

		if (
			is_admin() &&
			'post.php' === $pagenow &&
			function_exists( 'wc_get_page_id' ) &&
			is_a( $post, 'WP_Post' ) &&
			wc_get_page_id( 'shop' ) === $post->ID
		) {
			return false;
		}

		return $is_support;
	}

	/**
	 * Update the product archive title to "Shop".
	 *
	 * @param string $post_type_name Post type 'name' label.
	 * @param string $post_type      Post type.
	 *
	 * @return string
	 */
	public function update_product_archive_title( $post_type_name, $post_type ) {
		if (
			function_exists( 'is_shop' ) &&
			is_shop() &&
			'product' === $post_type
		) {
			return __( 'Shop', 'woocommerce' );
		}

		return $post_type_name;
	}
}
