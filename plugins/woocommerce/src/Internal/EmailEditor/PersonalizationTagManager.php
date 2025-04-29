<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\EmailEditor;

use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tag;
use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tags_Registry;

defined( 'ABSPATH' ) || exit;

/**
 * Manages personalization tags for WooCommerce emails.
 *
 * @internal
 */
class PersonalizationTagManager {

	/**
	 * Initialize the personalization tag manager.
	 *
	 * @internal
	 * @return void
	 */
	final public function init(): void {
		add_filter( 'woocommerce_email_editor_register_personalization_tags', array( $this, 'register_personalization_tags' ) );
	}

	/**
	 * Register WooCommerce personalization tags with the registry.
	 *
	 * @param Personalization_Tags_Registry $registry The personalization tags registry.
	 * @return Personalization_Tags_Registry
	 */
	public function register_personalization_tags( Personalization_Tags_Registry $registry ) {
		$registry->register(
			new Personalization_Tag(
				__( 'Shopper Email', 'woocommerce' ),
				'woocommerce/shopper-email',
				__( 'Shopper', 'woocommerce' ),
				function ( array $context ): string {
					if ( isset( $context['order'] ) ) {
						return $context['order']->get_billing_email() ?? '';
					}
					return $context['recipient_email'] ?? '';
				},
			)
		);

		$registry->register(
			new Personalization_Tag(
				__( 'Shopper First Name', 'woocommerce' ),
				'woocommerce/shopper-first-name',
				__( 'Shopper', 'woocommerce' ),
				function ( array $context ): string {
					if ( isset( $context['order'] ) ) {
						return $context['order']->get_billing_first_name() ?? '';
					} elseif ( isset( $context['wp_user'] ) ) {
						return $context['wp_user']->first_name ?? '';
					}
					return '';
				},
			)
		);

		$registry->register(
			new Personalization_Tag(
				__( 'Shopper Last Name', 'woocommerce' ),
				'woocommerce/shopper-last-name',
				__( 'Shopper', 'woocommerce' ),
				function ( array $context ): string {
					if ( isset( $context['order'] ) ) {
						return $context['order']->get_billing_last_name() ?? '';
					} elseif ( isset( $context['wp_user'] ) ) {
						return $context['wp_user']->last_name ?? '';
					}
					return '';
				},
			)
		);

		$registry->register(
			new Personalization_Tag(
				__( 'Shopper Full Name', 'woocommerce' ),
				'woocommerce/shopper-full-name',
				__( 'Shopper', 'woocommerce' ),
				function ( array $context ): string {
					if ( isset( $context['order'] ) ) {
						return $context['order']->get_formatted_billing_full_name() ?? '';
					} elseif ( isset( $context['wp_user'] ) ) {
						$first_name = $context['wp_user']->first_name ?? '';
						$last_name  = $context['wp_user']->last_name ?? '';
						return trim( "$first_name $last_name" );
					}
					return '';
				},
			)
		);

		$registry->register(
			new Personalization_Tag(
				__( 'Shopper Username', 'woocommerce' ),
				'woocommerce/shopper-username',
				__( 'Shopper', 'woocommerce' ),
				function ( array $context ): string {
					if ( isset( $context['wp_user'] ) ) {
						return stripslashes( $context['wp_user']->user_login ?? '' );
					}
					return '';
				},
			)
		);

		// Order Personalization Tags.
		$registry->register(
			new Personalization_Tag(
				__( 'Order Number', 'woocommerce' ),
				'woocommerce/order-number',
				__( 'Order', 'woocommerce' ),
				function ( array $context ): string {
					if ( ! isset( $context['order'] ) ) {
						return '';
					}
					return $context['order']->get_order_number() ?? '';
				},
			)
		);

		// Site Personalization Tags.
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

		// Store Personalization Tags.
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
				__( 'My Account URL', 'woocommerce' ),
				'woocommerce/my-account-url',
				__( 'Store', 'woocommerce' ),
				function (): string {
					return esc_attr( wc_get_page_permalink( 'myaccount' ) );
				},
			)
		);

		// Admin Order Note.
		// This is temporary untill we create it's block.
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
		return $registry;
	}
}
