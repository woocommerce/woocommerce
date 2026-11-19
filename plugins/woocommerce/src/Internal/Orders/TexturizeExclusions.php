<?php
/**
 * TexturizeExclusions class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Orders;

use Automattic\WooCommerce\Internal\OrderReviews\Endpoint;
use Automattic\WooCommerce\Internal\OrderWithdrawal\OrderWithdrawalEndpoint;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\Internal\StockNotifications\Frontend\MyAccountEndpoint;

/**
 * Stops wptexturize() changing customer details, such as emails like `o'neil--shop@48x17.com`.
 *
 * The tag is `address`. Templates put the email inside that element, and this class decides which block-theme requests skip it.
 *
 * @internal Just for internal use.
 *
 * @since 11.4.0
 */
class TexturizeExclusions implements RegisterHooksInterface {

	/**
	 * Tag wptexturize() skips. Customer emails sit inside this element.
	 */
	private const ADDRESS_TAG = 'address';

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
		if ( ! wp_is_block_theme() ) {
			return false;
		}

		if ( is_order_received_page() || is_view_order_page() || $this->is_review_order_page() ) {
			return true;
		}

		if ( is_wc_endpoint_url( OrderWithdrawalEndpoint::ENDPOINT_KEY ) || is_wc_endpoint_url( MyAccountEndpoint::ENDPOINT ) ) {
			return true;
		}

		return $this->queued_notices_contain_address_markup();
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
	 * Whether a queued notice contains an address element.
	 *
	 * Guest verification resends redirect to the product or shop page after wc_add_notice(), so those requests are not a fixed endpoint.
	 *
	 * @return bool
	 */
	private function queued_notices_contain_address_markup(): bool {
		if ( ! function_exists( 'WC' ) || ! WC()->session ) {
			return false;
		}

		$notices = wc_get_notices();
		if ( ! is_array( $notices ) ) {
			return false;
		}

		foreach ( $notices as $group ) {
			if ( ! is_array( $group ) ) {
				if ( is_string( $group ) && $this->contains_address_tag( $group ) ) {
					return true;
				}
				continue;
			}

			foreach ( $group as $notice ) {
				$message = '';
				if ( is_string( $notice ) ) {
					$message = $notice;
				} elseif ( is_array( $notice ) && isset( $notice['notice'] ) && is_string( $notice['notice'] ) ) {
					$message = $notice['notice'];
				}

				if ( $this->contains_address_tag( $message ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Whether a notice string contains the address tag.
	 *
	 * @param string $message Notice HTML.
	 * @return bool
	 */
	private function contains_address_tag( string $message ): bool {
		return '' !== $message && false !== strpos( $message, '<' . self::ADDRESS_TAG );
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
		if ( ! is_array( $tags ) || in_array( self::ADDRESS_TAG, $tags, true ) ) {
			return $tags;
		}

		$tags[] = self::ADDRESS_TAG;
		return $tags;
	}
}
