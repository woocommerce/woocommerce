<?php
namespace Automattic\WooCommerce\Blocks\Utils;

use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Blocks\Options;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\BlockTemplatesRegistry;
use Automattic\WooCommerce\Blocks\Templates\ProductCatalogTemplate;

/**
 * Utility methods used for serving block templates from WooCommerce Blocks.
 * {@internal This class and its methods should only be used within the BlockTemplateController.php and is not intended for public use.}
 */
class BlockTemplateUtils {
	/**
	 * Directory names for block templates
	 *
	 * Directory names conventions for block templates have changed with Gutenberg 12.1.0,
	 * however, for backwards-compatibility, we also keep the older conventions, prefixed
	 * with `DEPRECATED_`.
	 *
	 * @var array {
	 *     @var string DEPRECATED_TEMPLATES  Old directory name of the block templates directory.
	 *     @var string DEPRECATED_TEMPLATE_PARTS  Old directory name of the block template parts directory.
	 *     @var string TEMPLATES_DIR_NAME  Directory name of the block templates directory.
	 *     @var string TEMPLATE_PARTS_DIR_NAME  Directory name of the block template parts directory.
	 * }
	 */
	const DIRECTORY_NAMES = array(
		'DEPRECATED_TEMPLATES'      => 'block-templates',
		'DEPRECATED_TEMPLATE_PARTS' => 'block-template-parts',
		'TEMPLATES'                 => 'templates',
		'TEMPLATE_PARTS'            => 'parts',
	);

	const TEMPLATES_ROOT_DIR = 'templates';

	/**
	 * WooCommerce plugin slug
	 *
	 * This is used to save templates to the DB which are stored against this value in the wp_terms table.
	 *
	 * @var string
	 */
	const PLUGIN_SLUG = 'woocommerce/woocommerce';

	/**
	 * Deprecated WooCommerce plugin slug
	 *
	 * For supporting users who have customized templates under the incorrect plugin slug during the first release.
	 * More context found here: https://github.com/woocommerce/woocommerce-gutenberg-products-block/issues/5423.
	 *
	 * @var string
	 */
	const DEPRECATED_PLUGIN_SLUG = 'woocommerce';

	/**
	 * Returns the template matching the slug
	 *
	 * @param string $template_slug Slug of the template to retrieve.
	 *
	 * @return AbstractTemplate|AbstractTemplatePart|null
	 */
	public static function get_template( $template_slug ) {
		$block_templates_registry = Package::container()->get( BlockTemplatesRegistry::class );
		return $block_templates_registry->get_template( $template_slug );
	}

	/**
	 * Returns an array containing the references of
	 * the passed blocks and their inner blocks.
	 *
	 * @param array $blocks array of blocks.
	 *
	 * @return array block references to the passed blocks and their inner blocks.
	 */
	public static function flatten_blocks( &$blocks ) {
		$all_blocks = array();
		$queue      = array();
		foreach ( $blocks as &$block ) {
			$queue[] = &$block;
		}
		$queue_count = count( $queue );

		while ( $queue_count > 0 ) {
			$block = &$queue[0];
			array_shift( $queue );
			$all_blocks[] = &$block;

			if ( ! empty( $block['innerBlocks'] ) ) {
				foreach ( $block['innerBlocks'] as &$inner_block ) {
					$queue[] = &$inner_block;
				}
			}

			$queue_count = count( $queue );
		}

		return $all_blocks;
	}

	/**
	 * Parses wp_template content and injects the current theme's
	 * stylesheet as a theme attribute into each wp_template_part
	 *
	 * @param string $template_content serialized wp_template content.
	 *
	 * @return string Updated wp_template content.
	 */
	public static function inject_theme_attribute_in_content( $template_content ) {
		$has_updated_content = false;
		$new_content         = '';
		$template_blocks     = parse_blocks( $template_content );

		$blocks = self::flatten_blocks( $template_blocks );
		foreach ( $blocks as &$block ) {
			if (
				'core/template-part' === $block['blockName'] &&
				! isset( $block['attrs']['theme'] )
			) {
				$block['attrs']['theme'] = wp_get_theme()->get_stylesheet();
				$has_updated_content     = true;
			}
		}

		if ( $has_updated_content ) {
			foreach ( $template_blocks as &$block ) {
				$new_content .= serialize_block( $block );
			}

			return $new_content;
		}

		return $template_content;
	}

	/**
	 * Build a unified template object based a post Object.
	 * Important: This method is an almost identical duplicate from wp-includes/block-template-utils.php as it was not intended for public use. It has been modified to build templates from plugins rather than themes.
	 *
	 * @param \WP_Post $post Template post.
	 *
	 * @return \WP_Block_Template|\WP_Error Template.
	 */
	public static function build_template_result_from_post( $post ) {
		$terms = get_the_terms( $post, 'wp_theme' );

		if ( is_wp_error( $terms ) ) {
			return $terms;
		}

		if ( ! $terms ) {
			return new \WP_Error( 'template_missing_theme', __( 'No theme is defined for this template.', 'woocommerce' ) );
		}

		$theme          = $terms[0]->name;
		$has_theme_file = true;

		$template                 = new \WP_Block_Template();
		$template->wp_id          = $post->ID;
		$template->id             = $theme . '//' . $post->post_name;
		$template->theme          = $theme;
		$template->content        = $post->post_content;
		$template->slug           = $post->post_name;
		$template->source         = 'custom';
		$template->type           = $post->post_type;
		$template->description    = $post->post_excerpt;
		$template->title          = $post->post_title;
		$template->status         = $post->post_status;
		$template->has_theme_file = $has_theme_file;
		$template->is_custom      = false;
		$template->post_types     = array(); // Don't appear in any Edit Post template selector dropdown.

		if ( 'wp_template_part' === $post->post_type ) {
			$type_terms = get_the_terms( $post, 'wp_template_part_area' );
			if ( ! is_wp_error( $type_terms ) && false !== $type_terms ) {
				$template->area = $type_terms[0]->name;
			}
		}

		// We are checking 'woocommerce' to maintain classic templates which are saved to the DB,
		// prior to updating to use the correct slug.
		// More information found here: https://github.com/woocommerce/woocommerce-gutenberg-products-block/issues/5423.
		if ( self::PLUGIN_SLUG === $theme || self::DEPRECATED_PLUGIN_SLUG === strtolower( $theme ) ) {
			$template->origin = 'plugin';
		}

		/*
		* Run the block hooks algorithm introduced in WP 6.4 on the template content.
		*/
		if ( function_exists( 'inject_ignored_hooked_blocks_metadata_attributes' ) ) {
			$hooked_blocks = get_hooked_blocks();
			if ( ! empty( $hooked_blocks ) || has_filter( 'hooked_block_types' ) ) {
				$before_block_visitor = make_before_block_visitor( $hooked_blocks, $template );
				$after_block_visitor  = make_after_block_visitor( $hooked_blocks, $template );
				$blocks               = parse_blocks( $template->content );
				$template->content    = traverse_and_serialize_blocks( $blocks, $before_block_visitor, $after_block_visitor );
			}
		}

		return $template;
	}

	/**
	 * Build a unified template object based on a theme file.
	 *
	 * @internal Important: This method is an almost identical duplicate from wp-includes/block-template-utils.php as it was not intended for public use. It has been modified to build templates from plugins rather than themes.
	 *
	 * @param array|object $template_file Theme file.
	 * @param string       $template_type wp_template or wp_template_part.
	 *
	 * @return \WP_Block_Template Template.
	 */
	public static function build_template_result_from_file( $template_file, $template_type ) {
		$template_file = (object) $template_file;

		// If the theme has an archive-products.html template but does not have product taxonomy templates
		// then we will load in the archive-product.html template from the theme to use for product taxonomies on the frontend.
		$template_is_from_theme = 'theme' === $template_file->source;
		$theme_name             = wp_get_theme()->get( 'TextDomain' );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$template_content  = file_get_contents( $template_file->path );
		$template          = new \WP_Block_Template();
		$template->id      = $theme_name . '//' . $template_file->slug;
		$template->theme   = $theme_name;
		$template->content = self::inject_theme_attribute_in_content( $template_content );
		// Remove the term description block from the archive-product template
		// as the Product Catalog/Shop page doesn't have a description.
		if ( ProductCatalogTemplate::SLUG === $template_file->slug ) {
			$template->content = str_replace( '<!-- wp:term-description {"align":"wide"} /-->', '', $template->content );
		}
		// Plugin was agreed as a valid source value despite existing inline docs at the time of creating: https://github.com/WordPress/gutenberg/issues/36597#issuecomment-976232909.
		$template->source         = $template_file->source ? $template_file->source : 'plugin';
		$template->slug           = $template_file->slug;
		$template->type           = $template_type;
		$template->title          = ! empty( $template_file->title ) ? $template_file->title : self::get_block_template_title( $template_file->slug );
		$template->description    = ! empty( $template_file->description ) ? $template_file->description : self::get_block_template_description( $template_file->slug );
		$template->status         = 'publish';
		$template->has_theme_file = true;
		$template->origin         = 'theme';
		$template->is_custom      = false; // Templates loaded from the filesystem aren't custom, ones that have been edited and loaded from the DB are.
		$template->post_types     = array(); // Don't appear in any Edit Post template selector dropdown.
		$template->area           = self::get_block_template_area( $template->slug, $template_type );

		/*
		* Run the block hooks algorithm introduced in WP 6.4 on the template content.
		*/
		if ( function_exists( 'inject_ignored_hooked_blocks_metadata_attributes' ) ) {
			$before_block_visitor = '_inject_theme_attribute_in_template_part_block';
			$after_block_visitor  = null;
			$hooked_blocks        = get_hooked_blocks();
			if ( ! empty( $hooked_blocks ) || has_filter( 'hooked_block_types' ) ) {
				$before_block_visitor = make_before_block_visitor( $hooked_blocks, $template );
				$after_block_visitor  = make_after_block_visitor( $hooked_blocks, $template );
			}
			$blocks            = parse_blocks( $template->content );
			$template->content = traverse_and_serialize_blocks( $blocks, $before_block_visitor, $after_block_visitor );
		}

		return $template;
	}

	/**
	 * Build a new template object so that we can make Woo Blocks default templates available in the current theme should they not have any.
	 *
	 * @param string $template_file Block template file path.
	 * @param string $template_type wp_template or wp_template_part.
	 * @param string $template_slug Block template slug e.g. single-product.
	 * @param bool   $template_is_from_theme If the block template file is being loaded from the current theme instead of Woo Blocks.
	 *
	 * @return object Block template object.
	 */
	public static function create_new_block_template_object( $template_file, $template_type, $template_slug, $template_is_from_theme = false ) {
		$theme_name = wp_get_theme()->get( 'TextDomain' );

		$new_template_item = array(
			'slug'        => $template_slug,
			'id'          => $theme_name . '//' . $template_slug,
			'path'        => $template_file,
			'type'        => $template_type,
			'theme'       => $template_is_from_theme ? $theme_name : self::PLUGIN_SLUG,
			// Plugin was agreed as a valid source value despite existing inline docs at the time of creating: https://github.com/WordPress/gutenberg/issues/36597#issuecomment-976232909.
			'source'      => $template_is_from_theme ? 'theme' : 'plugin',
			'origin'      => 'theme',
			'title'       => self::get_block_template_title( $template_slug ),
			'description' => self::get_block_template_description( $template_slug ),
			'post_types'  => array(), // Don't appear in any Edit Post template selector dropdown.
		);

		return (object) $new_template_item;
	}

	/**
	 * Finds all nested template part file paths in a theme's directory.
	 *
	 * @param string $template_type wp_template or wp_template_part.
	 * @return array $path_list A list of paths to all template part files.
	 */
	public static function get_template_paths( $template_type ) {
		$wp_template_filenames = array(
			'archive-product.html',
			'order-confirmation.html',
			'page-cart.html',
			'page-checkout.html',
			'product-search-results.html',
			'single-product.html',
			'taxonomy-product_attribute.html',
			'taxonomy-product_cat.html',
			'taxonomy-product_tag.html',
		);

		if ( Features::is_enabled( 'launch-your-store' ) ) {
			$wp_template_filenames[] = 'coming-soon.html';
		}

		$wp_template_part_filenames = array(
			'checkout-header.html',
			'mini-cart.html',
		);

		/*
		* This may return the blockified directory for wp_templates.
		* At the moment every template file has a corresponding blockified file.
		* If we decide to add a new template file that doesn't, we will need to update this logic.
		*/
		$directory = self::get_templates_directory( $template_type );

		$path_list = array_map(
			function ( $filename ) use ( $directory ) {
				return $directory . DIRECTORY_SEPARATOR . $filename;
			},
			'wp_template' === $template_type ? $wp_template_filenames : $wp_template_part_filenames
		);

		return $path_list;
	}

	/**
	 * Gets the directory where templates of a specific template type can be found.
	 *
	 * @param string $template_type wp_template or wp_template_part.
	 *
	 * @return string
	 */
	public static function get_templates_directory( $template_type = 'wp_template' ) {
			$root_path                = dirname( __DIR__, 3 ) . '/' . self::TEMPLATES_ROOT_DIR . DIRECTORY_SEPARATOR;
			$templates_directory      = $root_path . self::DIRECTORY_NAMES['TEMPLATES'];
			$template_parts_directory = $root_path . self::DIRECTORY_NAMES['TEMPLATE_PARTS'];

		if ( 'wp_template_part' === $template_type ) {
			return $template_parts_directory;
		}

		if ( self::should_use_blockified_product_grid_templates() ) {
			return $templates_directory . '/blockified';
		}

		return $templates_directory;
	}

	/**
	 * Returns template title.
	 *
	 * @param string $template_slug The template slug (e.g. single-product).
	 * @return string Human friendly title.
	 */
	public static function get_block_template_title( $template_slug ) {
		$registered_template = self::get_template( $template_slug );
		if ( isset( $registered_template ) ) {
			return $registered_template->get_template_title();
		} else {
			// Human friendly title converted from the slug.
			return ucwords( preg_replace( '/[\-_]/', ' ', $template_slug ) );
		}
	}

	/**
	 * Returns template description.
	 *
	 * @param string $template_slug The template slug (e.g. single-product).
	 * @return string Template description.
	 */
	public static function get_block_template_description( $template_slug ) {
		$registered_template = self::get_template( $template_slug );
		if ( isset( $registered_template ) ) {
			return $registered_template->get_template_description();
		}
		return '';
	}

	/**
	 * Returns area for template parts.
	 *
	 * @param string $template_slug The template part slug (e.g. mini-cart).
	 * @param string $template_type Either `wp_template` or `wp_template_part`.
	 * @return string Template part area.
	 */
	public static function get_block_template_area( $template_slug, $template_type ) {
		if ( 'wp_template_part' === $template_type ) {
			$registered_template = self::get_template( $template_slug );
			if ( $registered_template && property_exists( $registered_template, 'template_area' ) ) {
				return $registered_template->template_area;
			}
		}
		return 'uncategorized';
	}

	/**
	 * Converts template paths into a slug
	 *
	 * @param string $path The template's path.
	 * @return string slug
	 */
	public static function generate_template_slug_from_path( $path ) {
		$template_extension = '.html';

		return basename( $path, $template_extension );
	}

	/**
	 * Gets the first matching template part within themes directories
	 *
	 * Since [Gutenberg 12.1.0](https://github.com/WordPress/gutenberg/releases/tag/v12.1.0), the conventions for
	 * block templates and parts directory has changed from `block-templates` and `block-templates-parts`
	 * to `templates` and `parts` respectively.
	 *
	 * This function traverses all possible combinations of directory paths where a template or part
	 * could be located and returns the first one which is readable, prioritizing the new convention
	 * over the deprecated one, but maintaining that one for backwards compatibility.
	 *
	 * @param string $template_slug  The slug of the template (i.e. without the file extension).
	 * @param string $template_type  Either `wp_template` or `wp_template_part`.
	 *
	 * @return string|null  The matched path or `null` if no match was found.
	 */
	public static function get_theme_template_path( $template_slug, $template_type = 'wp_template' ) {
		$template_filename      = $template_slug . '.html';
		$possible_templates_dir = 'wp_template' === $template_type ? array(
			self::DIRECTORY_NAMES['TEMPLATES'],
			self::DIRECTORY_NAMES['DEPRECATED_TEMPLATES'],
		) : array(
			self::DIRECTORY_NAMES['TEMPLATE_PARTS'],
			self::DIRECTORY_NAMES['DEPRECATED_TEMPLATE_PARTS'],
		);

		// Combine the possible root directory names with either the template directory
		// or the stylesheet directory for child themes.
		$possible_paths = array_reduce(
			$possible_templates_dir,
			function ( $carry, $item ) use ( $template_filename ) {
				$filepath = DIRECTORY_SEPARATOR . $item . DIRECTORY_SEPARATOR . $template_filename;

				$carry[] = get_stylesheet_directory() . $filepath;
				$carry[] = get_template_directory() . $filepath;

				return $carry;
			},
			array()
		);

		// Return the first matching.
		foreach ( $possible_paths as $path ) {
			if ( is_readable( $path ) ) {
				return $path;
			}
		}

		return null;
	}

	/**
	 * Check if the theme has a template. So we know if to load our own in or not.
	 *
	 * @param string $template_name name of the template file without .html extension e.g. 'single-product'.
	 * @return boolean
	 */
	public static function theme_has_template( $template_name ) {
		return ! ! self::get_theme_template_path( $template_name, 'wp_template' );
	}

	/**
	 * Check if the theme has a template. So we know if to load our own in or not.
	 *
	 * @param string $template_name name of the template file without .html extension e.g. 'single-product'.
	 * @return boolean
	 */
	public static function theme_has_template_part( $template_name ) {
		return ! ! self::get_theme_template_path( $template_name, 'wp_template_part' );
	}

	/**
	 * Checks to see if they are using a compatible version of WP, or if not they have a compatible version of the Gutenberg plugin installed.
	 *
	 * @param string $template_type Optional. Template type: `wp_template` or `wp_template_part`.
	 *                              Default `wp_template`.
	 * @return boolean
	 */
	public static function supports_block_templates( $template_type = 'wp_template' ) {
		if ( 'wp_template_part' === $template_type && ( wc_current_theme_is_fse_theme() || current_theme_supports( 'block-template-parts' ) ) ) {
			return true;
		} elseif ( 'wp_template' === $template_type && wc_current_theme_is_fse_theme() ) {
			return true;
		}
		return false;
	}

	/**
	 * Checks if we can fall back to the `archive-product` template for a given slug.
	 *
	 * `taxonomy-product_cat`, `taxonomy-product_tag`, `taxonomy-product_attribute` templates can
	 *  generally use the `archive-product` as a fallback if there are no specific overrides.
	 *
	 * @param string $template_slug Slug to check for fallbacks.
	 * @return boolean
	 */
	public static function template_is_eligible_for_product_archive_fallback( $template_slug ) {
		$registered_template = self::get_template( $template_slug );
		if ( $registered_template && isset( $registered_template->fallback_template ) ) {
			return ProductCatalogTemplate::SLUG === $registered_template->fallback_template;
		}
		return false;
	}

	/**
	 * Checks if we can fall back to an `archive-product` template stored on the db for a given slug.
	 *
	 * @param string $template_slug Slug to check for fallbacks.
	 * @param array  $db_templates Templates that have already been found on the db.
	 * @return boolean
	 */
	public static function template_is_eligible_for_product_archive_fallback_from_db( $template_slug, $db_templates ) {
		$eligible_for_fallback = self::template_is_eligible_for_product_archive_fallback( $template_slug );
		if ( ! $eligible_for_fallback ) {
			return false;
		}

		$array_filter = array_filter(
			$db_templates,
			function ( $template ) use ( $template_slug ) {
				return ProductCatalogTemplate::SLUG === $template->slug;
			}
		);

		return count( $array_filter ) > 0;
	}

	/**
	 * Gets the `archive-product` fallback template stored on the db for a given slug.
	 *
	 * @param string $template_slug Slug to check for fallbacks.
	 * @param array  $db_templates Templates that have already been found on the db.
	 * @return boolean|object
	 */
	public static function get_fallback_template_from_db( $template_slug, $db_templates ) {
		$eligible_for_fallback = self::template_is_eligible_for_product_archive_fallback( $template_slug );
		if ( ! $eligible_for_fallback ) {
			return false;
		}

		foreach ( $db_templates as $template ) {
			if ( ProductCatalogTemplate::SLUG === $template->slug ) {
				return $template;
			}
		}

		return false;
	}

	/**
	 * Checks if we can fall back to the `archive-product` file template for a given slug in the current theme.
	 *
	 * `taxonomy-product_cat`, `taxonomy-product_tag`, `taxonomy-attribute` templates can
	 *  generally use the `archive-product` as a fallback if there are no specific overrides.
	 *
	 * @param string $template_slug Slug to check for fallbacks.
	 * @return boolean
	 */
	public static function template_is_eligible_for_product_archive_fallback_from_theme( $template_slug ) {
		return self::template_is_eligible_for_product_archive_fallback( $template_slug )
			&& ! self::theme_has_template( $template_slug )
			&& self::theme_has_template( ProductCatalogTemplate::SLUG );
	}

	/**
	 * Sets the `has_theme_file` to `true` for templates with fallbacks
	 *
	 * There are cases (such as tags, categories and attributes) in which fallback templates
	 * can be used; so, while *technically* the theme doesn't have a specific file
	 * for them, it is important that we tell Gutenberg that we do, in fact,
	 * have a theme file (i.e. the fallback one).
	 *
	 * **Note:** this function changes the array that has been passed.
	 *
	 * It returns `true` if anything was changed, `false` otherwise.
	 *
	 * @param array  $query_result Array of template objects.
	 * @param object $template A specific template object which could have a fallback.
	 *
	 * @return boolean
	 */
	public static function set_has_theme_file_if_fallback_is_available( $query_result, $template ) {
		foreach ( $query_result as &$query_result_template ) {
			if (
				$query_result_template->slug === $template->slug
				&& $query_result_template->theme === $template->theme
			) {
				if ( self::template_is_eligible_for_product_archive_fallback_from_theme( $template->slug ) ) {
					$query_result_template->has_theme_file = true;
				}

				return true;
			}
		}

		return false;
	}

	/**
	 * Removes templates that were added to a theme's block-templates directory, but already had a customised version saved in the database.
	 *
	 * @param \WP_Block_Template[]|\stdClass[] $templates List of templates to run the filter on.
	 *
	 * @return array List of templates with duplicates removed. The customised alternative is preferred over the theme default.
	 */
	public static function remove_theme_templates_with_custom_alternative( $templates ) {

		// Get the slugs of all templates that have been customised and saved in the database.
		$customised_template_slugs = array_map(
			function ( $template ) {
				return $template->slug;
			},
			array_values(
				array_filter(
					$templates,
					function ( $template ) {
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
				function ( $template ) use ( $customised_template_slugs ) {
					// This template has been customised and saved as a post, so return it.
					return ! ( 'theme' === $template->source && in_array( $template->slug, $customised_template_slugs, true ) );
				}
			)
		);
	}

	/**
	 * Removes customized templates that shouldn't be available. That means customized templates based on the
	 * WooCommerce default template when there is a customized template based on the theme template.
	 *
	 * @param \WP_Block_Template[]|\stdClass[] $templates  List of templates to run the filter on.
	 * @param string                           $theme_slug Slug of the theme currently active.
	 *
	 * @return array Filtered list of templates with only relevant templates available.
	 */
	public static function remove_duplicate_customized_templates( $templates, $theme_slug ) {
		$filtered_templates = array_filter(
			$templates,
			function ( $template ) use ( $templates, $theme_slug ) {
				if ( $template->theme === $theme_slug ) {
					// This is a customized template based on the theme template, so it should be returned.
					return true;
				}
				// This is a template customized from the WooCommerce default template.
				// Only return it if there isn't a customized version of the theme template.
				$is_there_a_customized_theme_template = array_filter(
					$templates,
					function ( $theme_template ) use ( $template, $theme_slug ) {
						return $theme_template->slug === $template->slug && $theme_template->theme === $theme_slug;
					}
				);
				if ( $is_there_a_customized_theme_template ) {
					return false;
				}
				return true;
			},
		);

		return $filtered_templates;
	}

	/**
	 * Returns whether the blockified templates should be used or not.
	 * If the option is not stored on the db, we need to check if the current theme is a block one or not.
	 *
	 * @return boolean
	 */
	public static function should_use_blockified_product_grid_templates() {
		$use_blockified_templates = get_option( Options::WC_BLOCK_USE_BLOCKIFIED_PRODUCT_GRID_BLOCK_AS_TEMPLATE );

		if ( false === $use_blockified_templates ) {
			return wc_current_theme_is_fse_theme();
		}

		return wc_string_to_bool( $use_blockified_templates );
	}

	/**
	 * Returns whether the passed `$template` has the legacy template block.
	 *
	 * @param object $template The template object.
	 * @return boolean
	 */
	public static function template_has_legacy_template_block( $template ) {
		return has_block( 'woocommerce/legacy-template', $template->content );
	}

	/**
	 * Updates the title, description and area of a template to the correct values and to make them more user-friendly.
	 * For example, instead of:
	 * - Title: `Tag (product_tag)`
	 * - Description: `Displays taxonomy: Tag.`
	 * we display:
	 * - Title: `Products by Tag`
	 * - Description: `Displays products filtered by a tag.`.
	 *
	 * @param WP_Block_Template $template The template object.
	 * @param string            $template_type wp_template or wp_template_part.
	 *
	 * @return WP_Block_Template
	 */
	public static function update_template_data( $template, $template_type ) {
		if ( ! $template ) {
			return $template;
		}
		if ( empty( $template->title ) || $template->title === $template->slug ) {
			$template->title = self::get_block_template_title( $template->slug );
		}
		if ( empty( $template->description ) ) {
			$template->description = self::get_block_template_description( $template->slug );
		}
		if ( empty( $template->area ) || 'uncategorized' === $template->area ) {
			$template->area = self::get_block_template_area( $template->slug, $template_type );
		}

		return $template;
	}

	/**
	 * Gets the templates saved in the database.
	 *
	 * @param array  $slugs An array of slugs to retrieve templates for.
	 * @param string $template_type wp_template or wp_template_part.
	 *
	 * @return int[]|\WP_Post[] An array of found templates.
	 */
	public static function get_block_templates_from_db( $slugs = array(), $template_type = 'wp_template' ) {
		$check_query_args = array(
			'post_type'      => $template_type,
			'posts_per_page' => -1,
			'no_found_rows'  => true,
			'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => 'wp_theme',
					'field'    => 'name',
					'terms'    => array( self::DEPRECATED_PLUGIN_SLUG, self::PLUGIN_SLUG, get_stylesheet() ),
				),
			),
		);

		if ( is_array( $slugs ) && count( $slugs ) > 0 ) {
			$check_query_args['post_name__in'] = $slugs;
		}

		$check_query         = new \WP_Query( $check_query_args );
		$saved_woo_templates = $check_query->posts;

		return array_map(
			function ( $saved_woo_template ) {
				return self::build_template_result_from_post( $saved_woo_template );
			},
			$saved_woo_templates
		);
	}

	/**
	 * Gets the template part by slug
	 *
	 * @param string $slug The template part slug.
	 *
	 * @return string The template part content.
	 */
	public static function get_template_part( $slug ) {
		$templates_from_db = self::get_block_templates_from_db( array( $slug ), 'wp_template_part' );
		if ( count( $templates_from_db ) > 0 ) {
			$template_slug_to_load = $templates_from_db[0]->theme;
		} else {
			$theme_has_template    = self::theme_has_template_part( $slug );
			$template_slug_to_load = get_stylesheet();
		}
		$template_part = get_block_template( $template_slug_to_load . '//' . $slug, 'wp_template_part' );

		if ( $template_part && ! empty( $template_part->content ) ) {
			return $template_part->content;
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		return file_get_contents( self::get_templates_directory( 'wp_template_part' ) . DIRECTORY_SEPARATOR . $slug . '.html' );
	}
}
