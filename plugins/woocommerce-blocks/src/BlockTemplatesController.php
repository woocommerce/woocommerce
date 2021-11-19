<?php
namespace Automattic\WooCommerce\Blocks;

use Automattic\WooCommerce\Blocks\Utils\BlockTemplateUtils;

/**
 * BlockTypesController class.
 *
 * @internal
 */
class BlockTemplatesController {

	/**
	 * Holds the path for the directory where the block templates will be kept.
	 *
	 * @var string
	 */
	private $templates_directory;

	/**
	 * Directory name of the block template directory.
	 *
	 * @var string
	 */
	const TEMPLATES_DIR_NAME = 'block-templates';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->templates_directory = plugin_dir_path( __DIR__ ) . 'templates/' . self::TEMPLATES_DIR_NAME;
		$this->init();
	}

	/**
	 * Initialization method.
	 */
	protected function init() {
		add_action( 'template_redirect', array( $this, 'render_block_template' ) );
		add_filter( 'pre_get_block_template', array( $this, 'maybe_return_blocks_template' ), 10, 3 );
		add_filter( 'get_block_templates', array( $this, 'add_block_templates' ), 10, 3 );
	}

	/**
	 * This function checks if there's a blocks template (ultimately it resolves either a saved blocks template from the
	 * database or a template file in `woo-gutenberg-products-block/templates/block-templates/`)
	 * to return to pre_get_posts short-circuiting the query in Gutenberg.
	 *
	 * @param \WP_Block_Template|null $template Return a block template object to short-circuit the default query,
	 *                                               or null to allow WP to run its normal queries.
	 * @param string                  $id Template unique identifier (example: theme_slug//template_slug).
	 * @param array                   $template_type wp_template or wp_template_part.
	 *
	 * @return mixed|\WP_Block_Template|\WP_Error
	 */
	public function maybe_return_blocks_template( $template, $id, $template_type ) {
		if ( ! function_exists( 'gutenberg_get_block_template' ) ) {
			return $template;
		}
		$template_name_parts = explode( '//', $id );
		if ( count( $template_name_parts ) < 2 ) {
			return $template;
		}
		list( , $slug ) = $template_name_parts;

		// Remove the filter at this point because if we don't then this function will infinite loop.
		remove_filter( 'pre_get_block_template', array( $this, 'maybe_return_blocks_template' ), 10, 3 );

		// Check if the theme has a saved version of this template before falling back to the woo one. Please note how
		// the slug has not been modified at this point, we're still using the default one passed to this hook.
		$maybe_template = gutenberg_get_block_template( $id, $template_type );
		if ( null !== $maybe_template ) {
			add_filter( 'pre_get_block_template', array( $this, 'maybe_return_blocks_template' ), 10, 3 );
			return $maybe_template;
		}

		// Theme-based template didn't exist, try switching the theme to woocommerce and try again. This function has
		// been unhooked so won't run again.
		add_filter( 'get_block_template', array( $this, 'get_single_block_template' ), 10, 3 );
		$maybe_template = gutenberg_get_block_template( 'woocommerce//' . $slug, $template_type );

		// Re-hook this function, it was only unhooked to stop recursion.
		add_filter( 'pre_get_block_template', array( $this, 'maybe_return_blocks_template' ), 10, 3 );
		remove_filter( 'get_block_template', array( $this, 'get_single_block_template' ), 10, 3 );
		if ( null !== $maybe_template ) {
			return $maybe_template;
		}

		// At this point we haven't had any luck finding a template. Give up and let Gutenberg take control again.
		return $template;
	}

	/**
	 * Runs on the get_block_template hook. If a template is already found and passed to this function, then return it
	 * and don't run.
	 * If a template is *not* passed, try to look for one that matches the ID in the database, if that's not found defer
	 * to Blocks templates files. Priority goes: DB-Theme, DB-Blocks, Filesystem-Theme, Filesystem-Blocks.
	 *
	 * @param \WP_Block_Template $template The found block template.
	 * @param string             $id Template unique identifier (example: theme_slug//template_slug).
	 * @param array              $template_type wp_template or wp_template_part.
	 *
	 * @return mixed|null
	 */
	public function get_single_block_template( $template, $id, $template_type ) {

		// The template was already found before the filter runs, just return it immediately.
		if ( null !== $template ) {
			return $template;
		}

		$template_name_parts = explode( '//', $id );
		if ( count( $template_name_parts ) < 2 ) {
			return $template;
		}
		list( , $slug ) = $template_name_parts;

		// If this blocks template doesn't exist then we should just skip the function and let Gutenberg handle it.
		if ( ! $this->block_template_is_available( $slug ) ) {
			return $template;
		}

		$available_templates = $this->get_block_templates( array( $slug ) );
		return ( is_array( $available_templates ) && count( $available_templates ) > 0 )
			? BlockTemplateUtils::gutenberg_build_template_result_from_file( $available_templates[0], $available_templates[0]->type )
			: $template;
	}

	/**
	 * Add the block template objects to be used.
	 *
	 * @param array $query_result Array of template objects.
	 * @param array $query Optional. Arguments to retrieve templates.
	 * @param array $template_type wp_template or wp_template_part.
	 * @return array
	 */
	public function add_block_templates( $query_result, $query, $template_type ) {
		if ( ! function_exists( 'gutenberg_supports_block_templates' ) || ! gutenberg_supports_block_templates() || 'wp_template' !== $template_type ) {
			return $query_result;
		}

		$post_type      = isset( $query['post_type'] ) ? $query['post_type'] : '';
		$slugs          = isset( $query['slug__in'] ) ? $query['slug__in'] : array();
		$template_files = $this->get_block_templates( $slugs );

		// @todo: Add apply_filters to _gutenberg_get_template_files() in Gutenberg to prevent duplication of logic.
		foreach ( $template_files as $template_file ) {

			// Avoid adding the same template if it's already in the array of $query_result.
			if (
				array_filter(
					$query_result,
					function( $query_result_template ) use ( $template_file ) {
						return $query_result_template->slug === $template_file->slug &&
								$query_result_template->theme === $template_file->theme;
					}
				)
			) {
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
				$template = BlockTemplateUtils::gutenberg_build_template_result_from_file( $template_file, 'wp_template' );
			} else {
				$template_file->title = BlockTemplateUtils::convert_slug_to_title( $template_file->slug );
				$query_result[]       = $template_file;
				continue;
			}

			$is_not_custom   = false === array_search(
				wp_get_theme()->get_stylesheet() . '//' . $template_file->slug,
				array_column( $query_result, 'id' ),
				true
			);
			$fits_slug_query =
				! isset( $query['slug__in'] ) || in_array( $template_file->slug, $query['slug__in'], true );
			$fits_area_query =
				! isset( $query['area'] ) || $template_file->area === $query['area'];
			$should_include  = $is_not_custom && $fits_slug_query && $fits_area_query;
			if ( $should_include ) {
				$query_result[] = $template;
			}
		}

		$query_result = $this->remove_theme_templates_with_custom_alternative( $query_result );
		return $query_result;
	}

	/**
	 * Removes templates that were added to a theme's block-templates directory, but already had a customised version saved in the database.
	 *
	 * @param \WP_Block_Template[]|\stdClass[] $templates List of templates to run the filter on.
	 *
	 * @return array List of templates with duplicates removed. The customised alternative is preferred over the theme default.
	 */
	public function remove_theme_templates_with_custom_alternative( $templates ) {

		// Get the slugs of all templates that have been customised and saved in the database.
		$customised_template_slugs = array_map(
			function( $template ) {
				return $template->slug;
			},
			array_values(
				array_filter(
					$templates,
					function( $template ) {
						// This template has been customised and saved as a post.
						return 'custom' === $template->source;
					}
				)
			)
		);

		// Remove theme (i.e. filesystem) templates that have the same slug as a customised one. We don't need to check
		// for `woocommerce` in $template->source here because woocommerce templates won't have been added to $templates
		// if a saved version was found in the db. This only affects saved templates that were saved BEFORE a theme
		// template with the same slug was added.
		return array_values(
			array_filter(
				$templates,
				function( $template ) use ( $customised_template_slugs ) {
					// This template has been customised and saved as a post, so return it.
					return ! ( 'theme' === $template->source && in_array( $template->slug, $customised_template_slugs, true ) );
				}
			)
		);
	}

	/**
	 * Gets the templates saved in the database.
	 *
	 * @param array $slugs An array of slugs to retrieve templates for.
	 *
	 * @return int[]|\WP_Post[] An array of found templates.
	 */
	public function get_block_templates_from_db( $slugs = array() ) {
		$check_query_args = array(
			'post_type'      => 'wp_template',
			'posts_per_page' => -1,
			'no_found_rows'  => true,
			'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => 'wp_theme',
					'field'    => 'name',
					'terms'    => array( 'woocommerce', get_stylesheet() ),
				),
			),
		);
		if ( is_array( $slugs ) && count( $slugs ) > 0 ) {
			$check_query_args['post_name__in'] = $slugs;
		}
		$check_query         = new \WP_Query( $check_query_args );
		$saved_woo_templates = $check_query->posts;

		return array_map(
			function( $saved_woo_template ) {
				return BlockTemplateUtils::gutenberg_build_template_result_from_post( $saved_woo_template );
			},
			$saved_woo_templates
		);
	}

	/**
	 * Gets the templates from the WooCommerce blocks directory, skipping those for which a template already exists
	 * in the theme directory.
	 *
	 * @param string[] $slugs An array of slugs to filter templates by. Templates whose slug does not match will not be returned.
	 * @param array    $already_found_templates Templates that have already been found, these are customised templates that are loaded from the database.
	 *
	 * @return array Templates from the WooCommerce blocks plugin directory.
	 */
	public function get_block_templates_from_woocommerce( $slugs, $already_found_templates ) {
		$template_files = BlockTemplateUtils::gutenberg_get_template_paths( $this->templates_directory );
		$templates      = array();
		foreach ( $template_files as $template_file ) {
			$template_slug = substr(
				$template_file,
				strpos( $template_file, self::TEMPLATES_DIR_NAME . DIRECTORY_SEPARATOR ) + 1 + strlen( self::TEMPLATES_DIR_NAME ),
				-5
			);

			// This template does not have a slug we're looking for. Skip it.
			if ( is_array( $slugs ) && count( $slugs ) > 0 && ! in_array( $template_slug, $slugs, true ) ) {
				continue;
			}

			// If the theme already has a template, or the template is already in the list (i.e. it came from the
			// database) then we should not overwrite it with the one from the filesystem.
			if (
				$this->theme_has_template( $template_slug ) ||
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

			// At this point the template only exists in the Blocks filesystem and has not been saved in the DB,
			// or superseded by the theme.
			$new_template_item = array(
				'slug'        => $template_slug,
				'id'          => 'woocommerce//' . $template_slug,
				'path'        => $template_file,
				'type'        => 'wp_template',
				'theme'       => 'woocommerce',
				'source'      => 'woocommerce',
				'title'       => BlockTemplateUtils::convert_slug_to_title( $template_slug ),
				'description' => '',
				'post_types'  => array(), // Don't appear in any Edit Post template selector dropdown.
			);
			$templates[]       = (object) $new_template_item;
		}
		return $templates;
	}

	/**
	 * Get and build the block template objects from the block template files.
	 *
	 * @param array $slugs An array of slugs to retrieve templates for.
	 * @return array
	 */
	public function get_block_templates( $slugs = array() ) {
		$templates_from_db  = $this->get_block_templates_from_db( $slugs );
		$templates_from_woo = $this->get_block_templates_from_woocommerce( $slugs, $templates_from_db );
		return array_merge( $templates_from_db, $templates_from_woo );
	}

	/**
	 * Check if the theme has a template. So we know if to load our own in or not.
	 *
	 * @param string $template_name name of the template file without .html extension e.g. 'single-product'.
	 * @return boolean
	 */
	public function theme_has_template( $template_name ) {
		return is_readable( get_template_directory() . '/block-templates/' . $template_name . '.html' ) ||
			is_readable( get_stylesheet_directory() . '/block-templates/' . $template_name . '.html' );
	}

	/**
	 * Checks whether a block template with that name exists in Woo Blocks
	 *
	 * @param string $template_name Template to check.
	 * @return boolean
	 */
	public function block_template_is_available( $template_name ) {
		if ( ! $template_name ) {
			return false;
		}

		return is_readable(
			$this->templates_directory . '/' . $template_name . '.html'
		) || $this->get_block_templates( array( $template_name ) );
	}

	/**
	 * Renders the default block template from Woo Blocks if no theme templates exist.
	 */
	public function render_block_template() {
		if ( is_embed() || ! function_exists( 'gutenberg_supports_block_templates' ) || ! gutenberg_supports_block_templates() ) {
			return;
		}

		if (
			is_singular( 'product' ) &&
			! $this->theme_has_template( 'single-product' ) &&
			$this->block_template_is_available( 'single-product' )
		) {
			add_filter( 'woocommerce_has_block_template', '__return_true', 10, 0 );
		} elseif (
			( is_product_taxonomy() && is_tax( 'product_cat' ) ) &&
			! $this->theme_has_template( 'taxonomy-product_cat' ) &&
			$this->block_template_is_available( 'taxonomy-product_cat' )
		) {
			add_filter( 'woocommerce_has_block_template', '__return_true', 10, 0 );
		} elseif (
			( is_product_taxonomy() && is_tax( 'product_tag' ) ) &&
			! $this->theme_has_template( 'taxonomy-product_tag' ) &&
			$this->block_template_is_available( 'taxonomy-product_tag' )
		) {
			add_filter( 'woocommerce_has_block_template', '__return_true', 10, 0 );
		} elseif (
			( is_post_type_archive( 'product' ) || is_page( wc_get_page_id( 'shop' ) ) ) &&
			! $this->theme_has_template( 'archive-product' ) &&
			$this->block_template_is_available( 'archive-product' )
		) {
			add_filter( 'woocommerce_has_block_template', '__return_true', 10, 0 );
		}
	}
}
