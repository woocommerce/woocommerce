<?php
namespace Automattic\WooCommerce\Blocks\Templates;

/**
 * ComingSoonTemplate class.
 *
 * @internal
 */
class ComingSoonTemplate extends AbstractPageTemplate {

	/**
	 * The slug of the template.
	 *
	 * @var string
	 */
	const SLUG = 'coming-soon';

	/**
	 * Returns the title of the template.
	 *
	 * @return string
	 */
	public function get_template_title() {
		return _x( 'Page: Coming soon', 'Template name', 'woocommerce' );
	}

	/**
	 * Returns the description of the template.
	 *
	 * @return string
	 */
	public function get_template_description() {
		return __( 'Page template for Coming soon page.', 'woocommerce' );
	}

	/**
	 * Returns the page object assigned to this template/page.
	 *
	 * @return \WP_Post|null Post object or null.
	 */
	protected function get_placeholder_page() {
		return null;
	}

	/**
	 * True when viewing the coming soon page.
	 *
	 * @return boolean
	 */
	protected function is_active_template() {
		return false;
	}
}
