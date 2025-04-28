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
				__( 'Customer Email', 'woocommerce' ),
				'woocommerce/customer-email',
				__( 'Customer', 'woocommerce' ),
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
				__( 'Customer First Name', 'woocommerce' ),
				'woocommerce/customer-first-name',
				__( 'Customer', 'woocommerce' ),
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
				__( 'Customer Last Name', 'woocommerce' ),
				'woocommerce/customer-last-name',
				__( 'Customer', 'woocommerce' ),
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
				__( 'Customer Full Name', 'woocommerce' ),
				'woocommerce/customer-full-name',
				__( 'Customer', 'woocommerce' ),
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
				__( 'Customer Username', 'woocommerce' ),
				'woocommerce/customer-username',
				__( 'Customer', 'woocommerce' ),
				function ( array $context ): string {
					if ( isset( $context['wp_user'] ) ) {
						return stripslashes( $context['wp_user']->user_login ?? '' );
					}
					return '';
				},
			)
		);

		$registry->register(
			new Personalization_Tag(
				__( 'Customer Country', 'woocommerce' ),
				'woocommerce/customer-country',
				__( 'Customer', 'woocommerce' ),
				function ( array $context ): string {
					if ( isset( $context['order'] ) ) {
						$country_code = $context['order']->get_billing_country();
						return WC()->countries->countries[ $country_code ] ?? $country_code ?? '';
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

		$registry->register(
			new Personalization_Tag(
				__( 'Order Date', 'woocommerce' ),
				'woocommerce/order-date',
				__( 'Order', 'woocommerce' ),
				function ( array $context, array $parameters = array() ): string {
					if ( ! isset( $context['order'] ) ) {
						return '';
					}
					$format       = isset( $parameters['format'] ) && is_string( $parameters['format'] ) ? $parameters['format'] : wc_date_format();
					$date_created = $context['order']->get_date_created();
					if ( ! $date_created ) {
						return '';
					}
					return wc_format_datetime( $date_created, $format );
				},
				array(
					'format' => wc_date_format(),
				),
			)
		);

		$registry->register(
			new Personalization_Tag(
				__( 'Order Items', 'woocommerce' ),
				'woocommerce/order-items',
				__( 'Order', 'woocommerce' ),
				function ( array $context ): string {
					if ( ! isset( $context['order'] ) ) {
						return '';
					}
					$items = array();
					foreach ( $context['order']->get_items() as $item ) {
						$items[] = $item->get_name();
					}
					return implode( ', ', $items );
				},
			)
		);

		$registry->register(
			new Personalization_Tag(
				__( 'Order Subtotal', 'woocommerce' ),
				'woocommerce/order-subtotal',
				__( 'Order', 'woocommerce' ),
				function ( array $context ): string {
					if ( ! isset( $context['order'] ) ) {
						return '';
					}
					return (string) $context['order']->get_subtotal();
				},
			)
		);

		$registry->register(
			new Personalization_Tag(
				__( 'Order Tax', 'woocommerce' ),
				'woocommerce/order-tax',
				__( 'Order', 'woocommerce' ),
				function ( array $context ): string {
					if ( ! isset( $context['order'] ) ) {
						return '';
					}
					return (string) $context['order']->get_total_tax();
				},
			)
		);

		$registry->register(
			new Personalization_Tag(
				__( 'Order Total', 'woocommerce' ),
				'woocommerce/order-total',
				__( 'Order', 'woocommerce' ),
				function ( array $context ): string {
					if ( ! isset( $context['order'] ) ) {
						return '';
					}
					return (string) $context['order']->get_total();
				},
			)
		);

		$registry->register(
			new Personalization_Tag(
				__( 'Payment Method', 'woocommerce' ),
				'woocommerce/order-payment-method',
				__( 'Order', 'woocommerce' ),
				function ( array $context ): string {
					if ( ! isset( $context['order'] ) ) {
						return '';
					}
					return $context['order']->get_payment_method_title();
				},
			)
		);

		$registry->register(
			new Personalization_Tag(
				__( 'Payment URL', 'woocommerce' ),
				'woocommerce/order-payment-url',
				__( 'Order', 'woocommerce' ),
				function ( array $context ): string {
					if ( ! isset( $context['order'] ) ) {
						return '';
					}
					return $context['order']->get_checkout_payment_url();
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
				__( 'Store URL', 'woocommerce' ),
				'woocommerce/store-url',
				__( 'Store', 'woocommerce' ),
				function (): string {
					return wc_get_page_permalink( 'shop' );
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
					return WC()->mailer->get_store_address();
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
