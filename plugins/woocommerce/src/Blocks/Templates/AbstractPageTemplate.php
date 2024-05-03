<?php
namespace Automattic\WooCommerce\Blocks\Templates;

/**
 * AbstractPageTemplate class.
 *
 * Shared logic for page templates.
 *
 * @internal
 */
abstract class AbstractPageTemplate {
	/**
	 * Page Template functionality is only initialized when using a block theme.
	 */
	public function __construct() {
		if ( wc_current_theme_is_fse_theme() ) {
			$this->init();
		}
	}

	/**
	 * Initialization method.
	 */
	protected function init() {
		add_filter( 'page_template_hierarchy', array( $this, 'page_template_hierarchy' ), 1 );
		add_filter( 'pre_get_document_title', array( $this, 'page_template_title' ) );
	}

	/**
	 * Returns the template slug.
	 *
	 * @return string
	 */
	abstract public static function get_slug();

	/**
	 * Returns the page object assigned to this template/page.
	 *
	 * @return \WP_Post|null Post object or null.
	 */
	abstract protected function get_placeholder_page();

	/**
	 * Should return true on pages/endpoints/routes where the template should be shown.
	 *
	 * @return boolean
	 */
	abstract protected function is_active_template();

	/**
	 * Should return the title of the page, or an empty string if the page title should not be changed.
	 *
	 * @return string
	 */
	public static function get_template_title() {
		return '';
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
	public function page_template_hierarchy( $templates ) {
		if ( $this->is_active_template() ) {
			array_unshift( $templates, $this->get_slug() );
		}
		return $templates;
	}

	/**
	 * Filter the page title when the template is active.
	 *
	 * @param string $title Page title.
	 * @return string
	 */
	public function page_template_title( $title ) {
		if ( $this->is_active_template() && $this->get_template_title() ) {
			return $this->get_template_title();
		}
		return $title;
	}
}
