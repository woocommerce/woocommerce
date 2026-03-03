<?php
declare( strict_types=1 );
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
	 * The fallback template to render if the existing template is not available.
	 *
	 * @var string
	 */
	public string $fallback_template;

	/**
	 * Initialization method.
	 */
	public function init() {
		add_filter( 'taxonomy_template_hierarchy', array( $this, 'template_hierarchy' ), 1 );
		add_action( 'template_redirect', array( $this, 'render_block_template' ) );
	}

	/**
	 * Add the fallback template to the hierarchy, right after the current template.
	 *
	 * @param array $templates Templates that match the taxonomy_template_hierarchy.
	 */
	public function template_hierarchy( $templates ) {
		$index = array_search( static::SLUG, $templates, true );
		if ( false === $index ) {
			$index = array_search( static::SLUG . '.php', $templates, true );
		}

		if (
			false !== $index && (
				! array_key_exists( $index + 1, $templates ) || $templates[ $index + 1 ] !== $this->fallback_template
			) ) {
			array_splice( $templates, $index + 1, 0, $this->fallback_template );
		}

		return $templates;
	}

	/**
	 * This method is hooked to WordPress' 'template_redirect' action and allows
	 * template classes to:
	 * 1. Decide when block templates should be rendered based on the context.
	 * 2. Execute specific logic, such as managing the compatibility layer for
	 *    legacy template support.
	 *
	 * Child classes must implement this method to define their template
	 * rendering conditions and any additional template-specific behavior.
	 */
	abstract public function render_block_template();
}
