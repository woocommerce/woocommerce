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
		add_filter( 'get_block_templates', array( $this, 'add_block_templates' ), 10, 3 );
		add_action( 'template_redirect', array( $this, 'render_block_template' ) );
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
		if ( ! gutenberg_supports_block_templates() || 'wp_template' !== $template_type ) {
			return $query_result;
		}

		$post_type      = isset( $query['post_type'] ) ? $query['post_type'] : '';
		$template_files = $this->get_block_templates();

		// @todo: Add apply_filters to _gutenberg_get_template_files() in Gutenberg to prevent duplication of logic.
		foreach ( $template_files as $template_file ) {
			$template = BlockTemplateUtils::gutenberg_build_template_result_from_file( $template_file, 'wp_template' );

			if ( $post_type && ! $template->is_custom ) {
				continue;
			}

			if ( $post_type &&
				isset( $template->post_types ) &&
				! in_array( $post_type, $template->post_types, true )
			) {
				continue;
			}

			$is_not_custom   = false === array_search(
				wp_get_theme()->get_stylesheet() . '//' . $template_file['slug'],
				array_column( $query_result, 'id' ),
				true
			);
			$fits_slug_query =
				! isset( $query['slug__in'] ) || in_array( $template_file['slug'], $query['slug__in'], true );
			$fits_area_query =
				! isset( $query['area'] ) || $template_file['area'] === $query['area'];
			$should_include  = $is_not_custom && $fits_slug_query && $fits_area_query;
			if ( $should_include ) {
				$query_result[] = $template;
			}
		}

		return $query_result;
	}

	/**
	 * Get and build the block template objects from the block template files.
	 *
	 * @return array
	 */
	public function get_block_templates() {
		$template_files = BlockTemplateUtils::gutenberg_get_template_paths( $this->templates_directory );
		$templates      = array();

		foreach ( $template_files as $template_file ) {
			$template_slug = substr(
				$template_file,
				strpos( $template_file, self::TEMPLATES_DIR_NAME . DIRECTORY_SEPARATOR ) + 1 + strlen( self::TEMPLATES_DIR_NAME ),
				-5
			);

			// If the theme already has a template then there is no need to load ours in.
			if ( $this->theme_has_template( $template_slug ) ) {
				continue;
			}

			$new_template_item = array(
				'slug'  => $template_slug,
				'path'  => $template_file,
				'theme' => get_template_directory(),
				'type'  => 'wp_template',
			);
			$templates[]       = $new_template_item;
		}
		return $templates;
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
	public function default_block_template_is_available( $template_name ) {
		if ( ! $template_name ) {
			return false;
		}

		return is_readable(
			$this->templates_directory . '/' . $template_name . '.html'
		);
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
			$this->default_block_template_is_available( 'single-product' )
		) {
			add_filter( 'woocommerce_has_block_template', '__return_true', 10, 0 );
		} elseif (
			is_tax() &&
			! $this->theme_has_template( 'taxonomy-product_cat' ) &&
			$this->default_block_template_is_available( 'taxonomy-product_cat' )
		) {
			add_filter( 'woocommerce_has_block_template', '__return_true', 10, 0 );
		} elseif (
			( is_post_type_archive( 'product' ) || is_page( wc_get_page_id( 'shop' ) ) ) &&
			! $this->theme_has_template( 'archive-product' ) &&
			$this->default_block_template_is_available( 'archive-product' )
		) {
			add_filter( 'woocommerce_has_block_template', '__return_true', 10, 0 );
		}
	}
}
