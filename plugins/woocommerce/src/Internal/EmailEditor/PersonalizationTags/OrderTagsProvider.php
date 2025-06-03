<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\EmailEditor\PersonalizationTags;

use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tag;
use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tags_Registry;

/**
 * Provider for order-related personalization tags.
 *
 * @internal
 */
class OrderTagsProvider extends AbstractTagProvider {
	/**
	 * Register order tags with the registry.
	 *
	 * @param Personalization_Tags_Registry $registry The personalization tags registry.
	 * @return void
	 */
	public function register_tags( Personalization_Tags_Registry $registry ): void {
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
					return (string) $context['order']->get_subtotal() ?? '';
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
					return (string) $context['order']->get_total_tax() ?? '';
				},
			)
		);

		$registry->register(
			new Personalization_Tag(
				__( 'Order Discount', 'woocommerce' ),
				'woocommerce/order-discount',
				__( 'Order', 'woocommerce' ),
				function ( array $context ): string {
					if ( ! isset( $context['order'] ) ) {
						return '';
					}
					return wc_price( $context['order']->get_discount_total(), array( 'currency' => $context['order']->get_currency() ) );
				},
			)
		);

		$registry->register(
			new Personalization_Tag(
				__( 'Order Shipping', 'woocommerce' ),
				'woocommerce/order-shipping',
				__( 'Order', 'woocommerce' ),
				function ( array $context ): string {
					if ( ! isset( $context['order'] ) ) {
						return '';
					}
					return wc_price( $context['order']->get_shipping_total(), array( 'currency' => $context['order']->get_currency() ) );
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
					return (string) $context['order']->get_total() ?? '';
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
					return $context['order']->get_payment_method_title() ?? '';
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
					return $context['order']->get_checkout_payment_url() ?? '';
				},
			)
		);

		$registry->register(
			new Personalization_Tag(
				__( 'Order Transaction ID', 'woocommerce' ),
				'woocommerce/order-transaction-id',
				__( 'Order', 'woocommerce' ),
				function ( array $context ): string {
					if ( ! isset( $context['order'] ) ) {
						return '';
					}
					return $context['order']->get_transaction_id() ?? '';
				},
			)
		);

		$registry->register(
			new Personalization_Tag(
				__( 'Order Shipping Method', 'woocommerce' ),
				'woocommerce/order-shipping-method',
				__( 'Order', 'woocommerce' ),
				function ( array $context ): string {
					if ( ! isset( $context['order'] ) ) {
						return '';
					}
					return $context['order']->get_shipping_method() ?? '';
				},
			)
		);

		$registry->register(
			new Personalization_Tag(
				__( 'Order Shipping Address', 'woocommerce' ),
				'woocommerce/order-shipping-address',
				__( 'Order', 'woocommerce' ),
				function ( array $context ): string {
					if ( ! isset( $context['order'] ) ) {
						return '';
					}
					return $context['order']->get_formatted_shipping_address() ?? '';
				},
			)
		);

		$registry->register(
			new Personalization_Tag(
				__( 'Order Billing Address', 'woocommerce' ),
				'woocommerce/order-billing-address',
				__( 'Order', 'woocommerce' ),
				function ( array $context ): string {
					if ( ! isset( $context['order'] ) ) {
						return '';
					}
					return $context['order']->get_formatted_billing_address() ?? '';
				},
			)
		);

		$registry->register(
			new Personalization_Tag(
				__( 'Order View URL', 'woocommerce' ),
				'woocommerce/order-view-url',
				__( 'Order', 'woocommerce' ),
				function ( array $context ): string {
					if ( ! isset( $context['order'] ) ) {
						return '';
					}
					return $context['order']->get_view_order_url() ?? '';
				},
			)
		);

		$registry->register(
			new Personalization_Tag(
				__( 'Order Admin URL', 'woocommerce' ),
				'woocommerce/order-admin-url',
				__( 'Order', 'woocommerce' ),
				function ( array $context ): string {
					if ( ! isset( $context['order'] ) ) {
						return '';
					}
					return $context['order']->get_edit_order_url() ?? '';
				},
			)
		);

		$registry->register(
			new Personalization_Tag(
				__( 'Order Custom Field', 'woocommerce' ),
				'woocommerce/order-custom-field',
				__( 'Order', 'woocommerce' ),
				function ( array $context, array $parameters = array() ): string {
					if ( ! isset( $context['order'] ) || ! isset( $parameters['key'] ) ) {
						return '';
					}
					$field_key = sanitize_text_field( $parameters['key'] );
					return $context['order']->get_meta( $field_key ) ?? '';
				},
				array(
					'key' => '',
				),
			)
		);
	}
}
