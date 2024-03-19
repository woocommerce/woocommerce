<?php
namespace Automattic\WooCommerce\Blocks\Templates;

use Automattic\WooCommerce\Blocks\BlockTemplatesRegistry;
use Automattic\WooCommerce\Blocks\Utils\BlockTemplateUtils;

/**
 * AbstractTemplateWithFallback class.
 *
 * Shared logic for templates with fallbacks.
 *
 * @internal
 */
abstract class AbstractTemplateWithFallback extends AbstractTemplate {
	/**
	 * The fallback template to render if the existing fallback is not available.
	 *
	 * @var string
	 */
	public $fallback_template;

	/**
	 * Template part functionality is only initialized when using a theme that supports template parts.
	 */
	public function __construct() {
		if ( BlockTemplateUtils::supports_block_templates( 'wp_template_part' ) ) {
			BlockTemplatesRegistry::register_template( $this );
			$this->init();
		}
	}

	/**
	 * Initialization method.
	 */
	public function init() {
		// Make it searching for a template returns the default WooCommerce template.
		add_filter( 'pre_get_block_template', array( $this, 'get_block_template_fallback' ), 10, 3 );
		add_filter( 'taxonomy_template_hierarchy', array( $this, 'template_hierarchy' ), 1 );
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
		if ( BlockTemplateUtils::theme_has_template( self::SLUG ) ) {
			return null;
		}

		$wp_query_args  = array(
			'post_name__in' => array( $this->fallback_template, $slug ),
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

		if ( count( $posts ) > 0 && $this->fallback_template === $posts[0]->post_name ) {
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

		if ( count( $posts ) > 0 ) {
			$template           = _build_block_template_result_from_post( $posts[0] );
			$directory          = BlockTemplateUtils::get_templates_directory( 'wp_template' );
			$template_file_path = $directory . '/' . $this->fallback_template . '.html';
			$theme_slug         = wp_get_theme()->get_stylesheet();

			// Add fallback content when creating the page.
			if ( $template->id === $theme_slug . '//' . self::SLUG && ( ! isset( $template->content ) || '' === $template->content ) ) {
				$fallback_template_content = file_get_contents( $template_file_path );
				$template->content         = BlockTemplateUtils::inject_theme_attribute_in_content( $fallback_template_content );
				// Remove the term description block from the archive-product template
				// as the Product Catalog/Shop page doesn't have a description.
				$template->content = str_replace( '<!-- wp:term-description {"align":"wide"} /-->', '', $template->content );
			}
		}

		return $template;
	}

	/**
	 * When the page should be displaying the template, add it to the hierarchy.
	 *
	 * This places the template name e.g. `cart`, at the beginning of the template hierarchy array. The hook priority
	 * is 1 to ensure it runs first; other consumers e.g. extensions, could therefore inject their own template instead
	 * of this one when using the default priority of 10.
	 *
	 * @param array $templates Templates that match the pages_template_hierarchy.
	 */
	public function template_hierarchy( $templates ) {
		if ( $this->is_active_template() ) {
			array_unshift( $templates, $this->fallback_template );
			array_unshift( $templates, self::SLUG );

			// Make it searching for a template returns the default WooCommerce template.
			add_filter( 'get_block_templates', array( $this, 'add_block_templates' ), 10, 3 );
		}
		return $templates;
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
		// Is it's not the same slug, do nothing.
		if ( isset( $query['slug__in'] ) && ! in_array( self::SLUG, $query['slug__in'], true ) ) {
			return $query_result;
		}
		// If it's not the same template type, do nothing.
		if ( 'wp_template' !== $template_type ) {
			return $query_result;
		}
		// If the theme has the template, do nothing.
		if ( BlockTemplateUtils::theme_has_template( self::SLUG ) ) {
			return $query_result;
		}
		$directory          = BlockTemplateUtils::get_templates_directory( 'wp_template' );
		$template_file_path = $directory . '/' . $this->fallback_template . '.html';
		// If template is in DB, do nothing.
		if ( count( $query_result ) > 0 ) {
			return $query_result;
		}

		$template_object = BlockTemplateUtils::create_new_block_template_object( $template_file_path, 'wp_template', self::SLUG, true );
		$template        = BlockTemplateUtils::build_template_result_from_file( $template_object, 'wp_template' );
		$query_result[]  = $template;

		return $query_result;
	}

	/**
	 * Should return true on pages/endpoints/routes where the template should be shown.
	 *
	 * @return boolean
	 */
	abstract protected function is_active_template();

}
