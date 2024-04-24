<?php
namespace Automattic\WooCommerce\Blocks\Templates;

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
		$index = array_search( static::SLUG, $templates, true ) || array_search( static::SLUG . '.php', $templates, true );
		if (
			false !== $index && (
				! array_key_exists( $index + 1, $templates ) || $templates[ $index + 1 ] !== $this->fallback_template
			) ) {
			array_splice( $templates, $index + 1, 0, $this->fallback_template );
		}

		return $templates;
	}
}
