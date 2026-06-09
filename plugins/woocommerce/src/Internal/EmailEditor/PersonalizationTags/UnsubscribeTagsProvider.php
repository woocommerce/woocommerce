<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\EmailEditor\PersonalizationTags;

use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tag;
use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tags_Registry;
use Automattic\WooCommerce\Internal\EmailEditor\Integration;
use WC_Email;

/**
 * Provider for unsubscribe-link personalization tags.
 *
 * Exposes `woocommerce/email-unsubscribe-url` so any email class that
 * implements `get_unsubscribe_url()` (currently only the checkout-recovery
 * email) can surface a signed unsubscribe link inside the block-editor
 * template without having to register its own tag.
 *
 * Returns an empty string for email classes that don't expose the method,
 * which renders the surrounding `<a href="">` as a broken link — merchants
 * should only drop the unsubscribe block into emails where the kind
 * supports it.
 *
 * @internal
 */
class UnsubscribeTagsProvider extends AbstractTagProvider {
	/**
	 * Register unsubscribe tags with the registry.
	 *
	 * @param Personalization_Tags_Registry $registry The personalization tags registry.
	 * @return void
	 */
	public function register_tags( Personalization_Tags_Registry $registry ): void {
		$registry->register(
			new Personalization_Tag(
				__( 'Unsubscribe URL', 'woocommerce' ),
				'woocommerce/email-unsubscribe-url',
				__( 'Unsubscribe', 'woocommerce' ),
				function ( array $context ): string {
					$wc_email = $context['wc_email'] ?? null;
					if ( ! $wc_email instanceof WC_Email ) {
						return '';
					}
					if ( ! is_callable( array( $wc_email, 'get_unsubscribe_url' ) ) ) {
						return '';
					}
					return (string) $wc_email->get_unsubscribe_url();
				},
				array(),
				null,
				array( Integration::EMAIL_POST_TYPE ),
			)
		);
	}
}
