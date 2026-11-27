<?php
/**
 * TexturizeExclusions class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Orders;

use Automattic\WooCommerce\Internal\OrderReviews\Endpoint;
use Automattic\WooCommerce\Internal\OrderWithdrawal\OrderWithdrawalEndpoint;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Stops wptexturize() changing customer details, such as emails like `o'neil--shop@48x17.com`.
 *
 * @internal Just for internal use.
 *
 * @since 11.4.0
 */
class TexturizeExclusions implements RegisterHooksInterface {

	/**
	 * Class on the address element that wraps a stock notification verification notice.
	 *
	 * Only a queued notice with this class turns on the skip, so an address in another plugin's notice does not.
	 */
	public const VERIFICATION_EMAIL_CLASS = 'woocommerce-verification-email';

	/**
	 * Register hooks and filters.
	 */
	public function register(): void {
		add_action( 'template_redirect', array( $this, 'skip_address_texturize_on_order_pages' ) );
	}

	/**
	 * Skip texturizing address elements on block-theme requests that show a customer email.
	 *
	 * Block themes texturize the whole template HTML, including customer details.
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function skip_address_texturize_on_order_pages(): void {
		if ( $this->should_skip_address_texturize() ) {
			add_filter( 'no_texturize_tags', array( $this, 'add_address_tag' ) );
		}
	}

	/**
	 * Whether this block-theme request shows a customer email inside an address element.
	 *
	 * @return bool
	 */
	private function should_skip_address_texturize(): bool {
		global $wp;

		if ( ! wp_is_block_theme() ) {
			return false;
		}

		if ( is_order_received_page() || is_view_order_page() || $this->is_review_order_page() ) {
			return true;
		}

		// The withdrawal form renders on the endpoint key, which WooCommerce also fills from the slug.
		if ( isset( $wp->query_vars[ OrderWithdrawalEndpoint::ENDPOINT_KEY ] ) ) {
			return true;
		}

		return $this->queued_notices_contain_verification_email();
	}

	/**
	 * Whether this request is the Review Order page for an order.
	 *
	 * A review-order query var on any other page does not count, matching Endpoint::render_shortcode().
	 *
	 * @return bool
	 */
	private function is_review_order_page(): bool {
		global $wp;

		$page_id = (int) wc_get_page_id( Endpoint::PAGE_KEY );

		return $page_id > 0 && is_page( $page_id ) && isset( $wp->query_vars[ Endpoint::QUERY_VAR ] );
	}

	/**
	 * Whether a queued notice is a verification email notice.
	 *
	 * Guest resends redirect to the product or shop page, so they are not a fixed endpoint. wptexturize() only skips by tag name,
	 * so on that page view every address element keeps its plain punctuation, not just the notice.
	 *
	 * @return bool
	 */
	private function queued_notices_contain_verification_email(): bool {
		$marker = '<address class="' . self::VERIFICATION_EMAIL_CLASS . '">';

		foreach ( wc_get_notices() as $group ) {
			foreach ( (array) $group as $notice ) {
				$message = $notice['notice'] ?? $notice;
				if ( is_string( $message ) && false !== strpos( $message, $marker ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Add `address` to the tags wptexturize() skips.
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 *
	 * @param mixed $tags HTML element names.
	 * @return mixed
	 */
	public function add_address_tag( $tags ) {
		if ( ! is_array( $tags ) || in_array( 'address', $tags, true ) ) {
			return $tags;
		}

		$tags[] = 'address';
		return $tags;
	}
}
