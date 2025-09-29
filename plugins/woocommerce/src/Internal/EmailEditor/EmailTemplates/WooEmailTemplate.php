<?php declare(strict_types = 1);

namespace Automattic\WooCommerce\Internal\EmailEditor\EmailTemplates;

/**
 * Basic template for WooCommerce transactional emails used in the email editor.
 */
class WooEmailTemplate {
	/**
	 * The template slug.
	 */
	public const TEMPLATE_SLUG = 'wooemailtemplate';

	/**
	 * Get the template slug.
	 *
	 * @return string Template identifier.
	 */
	public function get_slug(): string {
		return self::TEMPLATE_SLUG;
	}

	/**
	 * Get the template title.
	 *
	 * @return string Localized template title.
	 */
	public function get_title(): string {
		return __( 'Woo Email Template', 'woocommerce' );
	}

	/**
	 * Get the template description.
	 *
	 * @return string Localized template description.
	 */
	public function get_description(): string {
		return __( 'Basic template for WooCommerce transactional emails used in the email editor', 'woocommerce' );
	}

	/**
	 * Get the template content.
	 *
	 * @return string HTML content for the template.
	 */
	public function get_content(): string {
		return '
<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|10","bottom":"var:preset|spacing|10","left":"var:preset|spacing|20","right":"var:preset|spacing|20"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--10);padding-right:var(--wp--preset--spacing--20);padding-bottom:var(--wp--preset--spacing--10);padding-left:var(--wp--preset--spacing--20)">
' . $this->get_site_logo_or_title() . '
</div>
<!-- /wp:group -->

<!-- wp:post-content {"lock":{"move":true,"remove":false},"layout":{"type":"default"}} /-->

<!-- wp:group {"style":{"spacing":{"padding":{"right":"var:preset|spacing|20","left":"var:preset|spacing|20","top":"var:preset|spacing|10","bottom":"var:preset|spacing|10"}}}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--10);padding-right:var(--wp--preset--spacing--20);padding-bottom:var(--wp--preset--spacing--10);padding-left:var(--wp--preset--spacing--20)"><!-- wp:paragraph {"align":"center","fontSize":"small","style":{"border":{"top":{"color":"var:preset|color|cyan-bluish-gray","width":"1px","style":"solid"},"right":[],"bottom":[],"left":[]},"spacing":{"padding":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20"}},"color":{"text":"#787c82"},"elements":{"link":{"color":{"text":"#787c82"}}}}} -->
<p class="has-text-align-center has-text-color has-link-color has-small-font-size" style="border-top-color:var(--wp--preset--color--cyan-bluish-gray);border-top-style:solid;border-top-width:1px;color:#787c82;padding-top:var(--wp--preset--spacing--20);padding-bottom:var(--wp--preset--spacing--20)">You received this email because you shopped at <!--[woocommerce/site-title]--></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
		';
	}

	/**
	 * Get the site logo or title.
	 *
	 * This is used to display the site logo or title in the email template.
	 *
	 * @return string HTML content for the site logo or title.
	 */
	private function get_site_logo_or_title(): string {
		$custom_logo = get_custom_logo();

		if ( ! empty( $custom_logo ) ) {
			// Use Site logo if available.
			return '<!-- wp:site-logo {"width":130, "isLink":false} /-->';
		}

		return '<!-- wp:site-title {"level":2} /-->';
	}
}
