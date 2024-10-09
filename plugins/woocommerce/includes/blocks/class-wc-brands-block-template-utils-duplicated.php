<?php // phpcs:disable WordPress.Files.FileName.InvalidClassFileName

declare( strict_types = 1);

use Automattic\WooCommerce\Blocks\Options;

//phpcs:disable Squiz.Classes.ClassFileName.NoMatch

/**
 * BlockTemplateUtils class used for serving block templates from Woo Blocks.
 * IMPORTANT: These methods have been duplicated from Gutenberg/lib/full-site-editing/block-templates.php as those functions are not for public usage.
 *
 * For internal use only by the Automattic\WooCommerce\Internal\Brands package.
 *
 * @version 9.4.0
 */
class BlockTemplateUtilsDuplicated {

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
	protected const DIRECTORY_NAMES = array(
		'DEPRECATED_TEMPLATES'      => 'block-templates',
		'DEPRECATED_TEMPLATE_PARTS' => 'block-template-parts',
		'TEMPLATES'                 => 'templates',
		'TEMPLATE_PARTS'            => 'parts',
	);

	/**
	 * WooCommerce plugin slug
	 *
	 * This is used to save templates to the DB which are stored against this value in the wp_terms table.
	 *
	 * @var string
	 */
	protected const PLUGIN_SLUG = 'woocommerce/woocommerce';

	/**
	 * Returns an array containing the references of
	 * the passed blocks and their inner blocks.
	 *
	 * @param array $blocks array of blocks.
	 *
	 * @return array block references to the passed blocks and their inner blocks.
	 */
	public static function gutenberg_flatten_blocks( &$blocks ) {
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
	public static function gutenberg_inject_theme_attribute_in_content( $template_content ) {
		$has_updated_content = false;
		$new_content         = '';
		$template_blocks     = parse_blocks( $template_content );

		$blocks = self::gutenberg_flatten_blocks( $template_blocks );
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
	 *
	 * @param \WP_Post $post Template post.
	 *
	 * @return \WP_Block_Template|\WP_Error Template.
	 */
	public static function gutenberg_build_template_result_from_post( $post ) {
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

		// We are checking 'woocommerce' to maintain legacy templates which are saved to the DB,
		// prior to updating to use the correct slug.
		// More information found here: https://github.com/woocommerce/woocommerce-gutenberg-products-block/issues/5423.
		if ( self::PLUGIN_SLUG === $theme || 'woocommerce' === strtolower( $theme ) ) {
			$template->origin = 'plugin';
		}

		return $template;
	}

	/**
	 * Build a unified template object based on a theme file.
	 *
	 * @param array|object $template_file Theme file.
	 * @param string       $template_type wp_template or wp_template_part.
	 *
	 * @return \WP_Block_Template Template.
	 */
	public static function gutenberg_build_template_result_from_file( $template_file, $template_type ) {
		$template_file = (object) $template_file;

		// If the theme has an archive-products.html template but does not have product taxonomy templates
		// then we will load in the archive-product.html template from the theme to use for product taxonomies on the frontend.
		$template_is_from_theme = 'theme' === $template_file->source;
		$theme_name             = wp_get_theme()->get( 'TextDomain' );

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$template_content  = file_get_contents( $template_file->path );
		$template          = new \WP_Block_Template();
		$template->id      = $template_is_from_theme ? $theme_name . '//' . $template_file->slug : self::PLUGIN_SLUG . '//' . $template_file->slug;
		$template->theme   = $template_is_from_theme ? $theme_name : self::PLUGIN_SLUG;
		$template->content = self::gutenberg_inject_theme_attribute_in_content( $template_content );
		// Plugin was agreed as a valid source value despite existing inline docs at the time of creating: https://github.com/WordPress/gutenberg/issues/36597#issuecomment-976232909.
		$template->source         = $template_file->source ? $template_file->source : 'plugin';
		$template->slug           = $template_file->slug;
		$template->type           = $template_type;
		$template->title          = ! empty( $template_file->title ) ? $template_file->title : self::convert_slug_to_title( $template_file->slug );
		$template->status         = 'publish';
		$template->has_theme_file = true;
		$template->origin         = $template_file->source;
		$template->is_custom      = false; // Templates loaded from the filesystem aren't custom, ones that have been edited and loaded from the DB are.
		$template->post_types     = array(); // Don't appear in any Edit Post template selector dropdown.
		$template->area           = 'uncategorized';
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
			'id'          => $template_is_from_theme ? $theme_name . '//' . $template_slug : self::PLUGIN_SLUG . '//' . $template_slug,
			'path'        => $template_file,
			'type'        => $template_type,
			'theme'       => $template_is_from_theme ? $theme_name : self::PLUGIN_SLUG,
			// Plugin was agreed as a valid source value despite existing inline docs at the time of creating: https://github.com/WordPress/gutenberg/issues/36597#issuecomment-976232909.
			'source'      => $template_is_from_theme ? 'theme' : 'plugin',
			'title'       => self::convert_slug_to_title( $template_slug ),
			'description' => '',
			'post_types'  => array(), // Don't appear in any Edit Post template selector dropdown.
		);

		return (object) $new_template_item;
	}


	/**
	 * Converts template slugs into readable titles.
	 *
	 * @param string $template_slug The templates slug (e.g. single-product).
	 * @return string Human friendly title converted from the slug.
	 */
	public static function convert_slug_to_title( $template_slug ) {
		switch ( $template_slug ) {
			case 'single-product':
				return __( 'Single Product', 'woocommerce' );
			case 'archive-product':
				return __( 'Product Archive', 'woocommerce' );
			case 'taxonomy-product_cat':
				return __( 'Product Category', 'woocommerce' );
			case 'taxonomy-product_tag':
				return __( 'Product Tag', 'woocommerce' );
			default:
				// Replace all hyphens and underscores with spaces.
				return ucwords( preg_replace( '/[\-_]/', ' ', $template_slug ) );
		}
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

				$carry[] = get_template_directory() . $filepath;
				$carry[] = get_stylesheet_directory() . $filepath;

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
		return (bool) self::get_theme_template_path( $template_name, 'wp_template' );
	}

	/**
	 * Check if the theme has a template. So we know if to load our own in or not.
	 *
	 * @param string $template_name name of the template file without .html extension e.g. 'single-product'.
	 * @return boolean
	 */
	public static function theme_has_template_part( $template_name ) {
		return (bool) self::get_theme_template_path( $template_name, 'wp_template_part' );
	}

	/**
	 * Checks to see if they are using a compatible version of WP, or if not they have a compatible version of the Gutenberg plugin installed.
	 *
	 * @return boolean
	 */
	public static function supports_block_templates() {
		if (
			( ! function_exists( 'wp_is_block_theme' ) || ! wp_is_block_theme() ) &&
			( ! function_exists( 'gutenberg_supports_block_templates' ) || ! gutenberg_supports_block_templates() )
		) {
			return false;
		}

		return true;
	}

	/**
	 * Returns whether the blockified templates should be used or not.
	 *
	 * First, we need to make sure WordPress version is higher than 6.1 (lowest that supports Products block).
	 * Then, if the option is not stored on the db, we need to check if the current theme is a block one or not.
	 *
	 * @return boolean
	 */
	public static function should_use_blockified_product_grid_templates() {
		$minimum_wp_version = '6.1';

		if ( version_compare( $GLOBALS['wp_version'], $minimum_wp_version, '<' ) ) {
			return false;
		}

		$use_blockified_templates = wc_string_to_bool( get_option( Options::WC_BLOCK_USE_BLOCKIFIED_PRODUCT_GRID_BLOCK_AS_TEMPLATE ) );

		if ( false === $use_blockified_templates ) {
			return function_exists( 'wc_current_theme_is_fse_theme' ) && wc_current_theme_is_fse_theme();
		}

		return $use_blockified_templates;
	}
}
