<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\EmailEditor\PersonalizationTags;

use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tag;
use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tags_Registry;
use Automattic\WooCommerce\Internal\EmailEditor\Integration;
use Automattic\WooCommerce\Internal\Orders\PointOfSaleOrderUtil;
use Automattic\WooCommerce\Internal\Settings\PointOfSaleDefaultSettings;

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
				function ( array $context ): string {
					if ( isset( $context['order'] ) && PointOfSaleOrderUtil::is_pos_order( $context['order'] ) ) {
						$store_name = get_option( 'woocommerce_pos_store_name' );
						return htmlspecialchars_decode( empty( $store_name ) ? PointOfSaleDefaultSettings::get_default_store_name() : $store_name, ENT_QUOTES );
					}
					return htmlspecialchars_decode( get_bloginfo( 'name' ) );
				},
				array(),
				null,
				array( Integration::EMAIL_POST_TYPE ),
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
				array(),
				null,
				array( Integration::EMAIL_POST_TYPE ),
			)
		);
	}
}
