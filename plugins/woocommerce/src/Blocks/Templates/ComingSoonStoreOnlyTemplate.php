<?php
namespace Automattic\WooCommerce\Blocks\Templates;

/**
 * ComingSoonStoreOnlyTemplate class.
 *
 * @internal
 */
class ComingSoonStoreOnlyTemplate extends AbstractPageTemplate {

	/**
	 * The slug of the template.
	 *
	 * @var string
	 */
	const SLUG = 'page-coming-soon-store-only';

	/**
	 * Returns the title of the template.
	 *
	 * @return string
	 */
	public function get_template_title() {
		return _x( 'Page: Coming soon store only', 'Template name', 'woocommerce' );
	}

	/**
	 * Returns the description of the template.
	 *
	 * @return string
	 */
	public function get_template_description() {
		return __( 'Page template for Coming soon page when access is restricted for store pages.', 'woocommerce' );
	}

	/**
	 * Returns the page object assigned to this template/page.
	 *
	 * @return \WP_Post|null Post object or null.
	 */
	protected function get_placeholder_page() {
		$page_id = get_option( 'woocommerce_coming_soon_page_id' );
		return $page_id ? get_post( $page_id ) : null;
	}

	/**
	 * True when viewing the coming soon page.
	 *
	 * @return boolean
	 */
	protected function is_active_template() {
		global $post;
		$placeholder = $this->get_placeholder_page();
		return null !== $placeholder && $post instanceof \WP_Post && $placeholder->post_name === $post->post_name;
	}
}
