<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\EmailEditor\PersonalizationTags;

use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tag;
use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tags_Registry;

/**
 * Provider for store-related personalization tags.
 *
 * @internal
 */
class StoreTagsProvider extends AbstractTagProvider {
	/**
	 * Register store tags with the registry.
	 *
	 * @param Personalization_Tags_Registry $registry The personalization tags registry.
	 * @return void
	 */
	public function register_tags( Personalization_Tags_Registry $registry ): void {
		$registry->register(
			new Personalization_Tag(
				__( 'Store Email', 'woocommerce' ),
				'woocommerce/store-email',
				__( 'Store', 'woocommerce' ),
				function ( array $context ): string {
					if ( isset( $context['wc_email'], $context['wc_email']->get_from_address ) ) {
						return $context['wc_email']->get_from_address();
					}
					return get_option( 'admin_email' );
				},
			)
		);

		$registry->register(
			new Personalization_Tag(
				__( 'Store URL', 'woocommerce' ),
				'woocommerce/store-url',
				__( 'Store', 'woocommerce' ),
				function (): string {
					return esc_attr( wc_get_page_permalink( 'shop' ) );
				},
			)
		);

		$registry->register(
			new Personalization_Tag(
				__( 'Store Name', 'woocommerce' ),
				'woocommerce/store-name',
				__( 'Store', 'woocommerce' ),
				function ( array $context ): string {
					if ( isset( $context['wc_email'] ) && ! empty( $context['wc_email']->get_from_name() ) ) {
						return $context['wc_email']->get_from_name();
					}

					return wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
				},
			)
		);

		$registry->register(
			new Personalization_Tag(
				__( 'Store Address', 'woocommerce' ),
				'woocommerce/store-address',
				__( 'Store', 'woocommerce' ),
				function (): string {
					return WC()->mailer->get_store_address() ?? '';
				},
			)
		);

		$registry->register(
			new Personalization_Tag(
				__( 'My Account URL', 'woocommerce' ),
				'woocommerce/my-account-url',
				__( 'Store', 'woocommerce' ),
				function (): string {
					return esc_attr( wc_get_page_permalink( 'myaccount' ) );
				},
			)
		);

		$registry->register(
			new Personalization_Tag(
				__( 'Admin Order Note', 'woocommerce' ),
				'woocommerce/admin-order-note',
				__( 'Store', 'woocommerce' ),
				function ( array $context ): string {
					if ( isset( $context['wc_email'], $context['wc_email']->customer_note ) ) {
						return wptexturize( $context['wc_email']->customer_note );
					}
					return '';
				},
			)
		);
	}
}
