<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\EmailEditor\PersonalizationTags;

use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tag;
use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tags_Registry;

/**
 * Provider for site-related personalization tags.
 *
 * @internal
 */
class SiteTagsProvider extends AbstractTagProvider {
	/**
	 * Register site tags with the registry.
	 *
	 * @param Personalization_Tags_Registry $registry The personalization tags registry.
	 * @return void
	 */
	public function register_tags( Personalization_Tags_Registry $registry ): void {
		$registry->register(
			new Personalization_Tag(
				__( 'Site Title', 'woocommerce' ),
				'woocommerce/site-title',
				__( 'Site', 'woocommerce' ),
				function (): string {
					return htmlspecialchars_decode( get_bloginfo( 'name' ) );
				},
			)
		);

		$registry->register(
			new Personalization_Tag(
				__( 'Homepage URL', 'woocommerce' ),
				'woocommerce/site-homepage-url',
				__( 'Site', 'woocommerce' ),
				function (): string {
					return get_bloginfo( 'url' );
				},
			)
		);
	}
}
