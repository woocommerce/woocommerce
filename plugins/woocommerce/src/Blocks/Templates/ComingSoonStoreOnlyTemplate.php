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
	 * Initialization method.
	 */
	public function init() {
		parent::init();
	}

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
		// This isn't quite right. We need to get the page ID from the option.
		$page_id = wc_get_page_id( 'coming-soon-store-only' );
		return $page_id ? get_post( $page_id ) : null;
	}

	/**
	 * True when viewing the cart page or cart endpoint.
	 *
	 * @return boolean
	 */
	protected function is_active_template() {
		global $post;
		$placeholder = $this->get_placeholder_page();
		return null !== $placeholder && $post instanceof \WP_Post && $placeholder->post_name === $post->post_name;
	}
}
