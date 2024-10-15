/**
 * External dependencies
 */
// @ts-expect-error -- @wordpress/element doesn't export createRoot until WP6.2
// eslint-disable-next-line @woocommerce/dependency-group
import { createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { PaymentPromotionRow } from './payment-promotion-row';

const PAYMENT_METHOD_PROMOTIONS = [
	{
		gatewayId: 'pre_install_woocommerce_payments_promotion',
		pluginSlug: 'woocommerce-payments',
		url: 'https://woocommerce.com/payments/?utm_medium=product',
	},
];

PAYMENT_METHOD_PROMOTIONS.forEach( ( paymentMethod ) => {
	const container = document.querySelector(
		`[data-gateway_id="${ paymentMethod.gatewayId }"]`
	);

	if ( container ) {
		const columns = [ ...container.children ].map( ( child ) => {
			return {
				className: child.className,
				html: child.innerHTML,
				width: child.getAttribute( 'width' ) || undefined,
			};
		} );
		const title = container.getElementsByClassName(
			'wc-payment-gateway-method-title'
		);
		const subTitle = container.getElementsByClassName( 'gateway-subtitle' );

		createRoot( container ).render(
			<PaymentPromotionRow
				columns={ columns }
				paymentMethod={ paymentMethod }
				title={ title.length === 1 ? title[ 0 ].innerHTML : undefined }
				subTitleContent={
					subTitle.length === 1 ? subTitle[ 0 ].innerHTML : undefined
				}
			/>
		);
	}
} );
