<?php declare(strict_types = 1);

namespace Automattic\WooCommerce\Internal\EmailEditor\EmailPatterns;

use Automattic\WooCommerce\EmailEditor\Engine\Patterns\Abstract_Pattern;
use Automattic\WooCommerce\Internal\EmailEditor\Integration;

/**
 * Pattern class for WooCommerce email content.
 *
 * Provides a default content pattern that can be used in WooCommerce email templates.
 */
class WooEmailContentPattern extends Abstract_Pattern {
	/**
	 * Pattern name identifier.
	 *
	 * @var string
	 */
	public $name = 'woo-email-content-pattern';

	/**
	 * Allowed block types for this pattern.
	 *
	 * @var array
	 */
	public $block_types = array();

	/**
	 * Template types where this pattern can be used.
	 *
	 * @var array
	 */
	public $template_types = array( 'email-template' );    // Required.

	/**
	 * Categories this pattern belongs to.
	 *
	 * @var array
	 */
	public $categories = array( 'email-contents' );        // Optional.

	/**
	 * Pattern namespace.
	 *
	 * @var string
	 */
	public $namespace = 'woocommerce';      // Required.

	/**
	 * List of supported post types.
	 *
	 * @var string[]
	 */
	protected $post_types = array( Integration::EMAIL_POST_TYPE );
	/**
	 * Get the pattern content.
	 *
	 * @return string HTML content for the pattern.
	 */
	public function get_content(): string {
		return '<!-- wp:group {"style":{"spacing":{"padding":{"right":"var:preset|spacing|20","left":"var:preset|spacing|20"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-right:var(--wp--preset--spacing--20);padding-left:var(--wp--preset--spacing--20)"><!-- wp:heading -->
<h2 class="wp-block-heading">Woo Email Content</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Here comes content composed of supported core blocks and Woo transactional email block(s).</p>
<!-- /wp:paragraph -->

<!-- wp:woocommerce/email-content {"lock":{"move":false,"remove":true}} -->
<div class="wp-block-woocommerce-email-content">##WOO_CONTENT##</div>
<!-- /wp:woocommerce/email-content -->

<!-- wp:buttons {"layout":{"justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"style":{"color":{"background":"#873eff"}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-background wp-element-button" style="background-color:#873eff">Shop now</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->';
	}

	/**
	 * Get the pattern title.
	 *
	 * @return string Localized pattern title.
	 */
	public function get_title(): string {
		/* translators: Name of a content pattern used as starting content of an email */
		return __( 'Woo Email Content Pattern', 'woocommerce' );
	}
}
