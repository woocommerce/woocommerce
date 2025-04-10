<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\Templates;

/**
 * ComingSoonSocialLinksTemplate class.
 *
 * @internal
 */
class ComingSoonSocialLinksTemplate extends AbstractTemplatePart {

	/**
	 * The slug of the template.
	 *
	 * @var string
	 */
	const SLUG = 'coming-soon-social-links';

	/**
	 * The template part area where the template part belongs.
	 *
	 * @var string
	 */
	public $template_area = 'uncategorized';

	/**
	 * Initialization method.
	 */
	public function init() {}

	/**
	 * Returns the title of the template.
	 *
	 * @return string
	 */
	public function get_template_title() {
		return _x( 'Coming soon social links', 'Template name', 'woocommerce' );
	}

	/**
	 * Returns the description of the template.
	 *
	 * @return string
	 */
	public function get_template_description() {
		return __( 'Reusable template part for displaying social links on the coming soon page.', 'woocommerce' );
	}

	/**
	 * Returns the page object assigned to this template/page.
	 *
	 * @return \WP_Post|null Post object or null.
	 */
	protected function get_placeholder_page() {
		return null;
	}
}
