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
		add_filter( 'taxonomy_template_hierarchy', array( $this, 'template_hierarchy' ), 1 );
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
			array_unshift( $templates, $this->slug );

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
		// Is it's not the same area, do nothing.
		if ( isset( $query['area'] ) && 'wp_template' !== $query['area'] ) {
			return $query_result;
		}
		// Is it's not the same slug, do nothing.
		if ( isset( $query['slug__in'] ) && ! in_array( $this->slug, $query['slug__in'], true ) ) {
			return $query_result;
		}
		// If it's not the same template type, do nothing.
		if ( 'wp_template' !== $template_type ) {
			return $query_result;
		}
		// If template is in DB, do nothing.
		if ( count( $query_result ) > 0 ) {
			return $query_result;
		}
		// If template is in theme, do nothing.
		if ( BlockTemplateUtils::theme_has_template( $this->slug ) ) {
			return $query_result;
		}

		$directory          = BlockTemplateUtils::get_templates_directory( 'wp_template' );
		$template_file_path = $directory . '/' . $this->slug . '.html';
		$template_object    = BlockTemplateUtils::create_new_block_template_object( $template_file_path, 'wp_template', $this->slug, true );
		$template           = BlockTemplateUtils::build_template_result_from_file( $template_object, 'wp_template' );
		$query_result[]     = $template;

		return $query_result;
	}

	/**
	 * Should return true on pages/endpoints/routes where the template should be shown.
	 *
	 * @return boolean
	 */
	abstract protected function is_active_template();

}
