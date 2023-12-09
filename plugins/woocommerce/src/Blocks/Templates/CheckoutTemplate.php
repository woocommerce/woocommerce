<?php
namespace Automattic\WooCommerce\Blocks\Templates;

use Automattic\WooCommerce\Blocks\Utils\BlockTemplateMigrationUtils;

/**
 * CheckoutTemplate class.
 *
 * @internal
 */
class CheckoutTemplate extends AbstractPageTemplate {
	/**
	 * Template slug.
	 *
	 * @return string
	 */
	public static function get_slug() {
		return 'page-checkout';
	}

	/**
	 * Returns the page object assigned to this template/page.
	 *
	 * @return \WP_Post|null Post object or null.
	 */
	protected function get_placeholder_page() {
		$page_id = wc_get_page_id( 'checkout' );
		return $page_id ? get_post( $page_id ) : null;
	}

	/**
	 * True when viewing the checkout page or checkout endpoint.
	 *
	 * @return boolean
	 */
	protected function is_active_template() {

		if ( ! BlockTemplateMigrationUtils::has_migrated_page( 'checkout' ) ) {
			return false;
		}

		global $post;
		$placeholder = $this->get_placeholder_page();
		return null !== $placeholder && $post instanceof \WP_Post && $placeholder->post_name === $post->post_name;
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
			array_unshift( $templates, 'checkout' );
		}
		return $templates;
	}
}
