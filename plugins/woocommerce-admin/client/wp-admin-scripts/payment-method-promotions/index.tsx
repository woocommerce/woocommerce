/**
 * External dependencies
 */
import { render } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { PaymentPromotionRow } from './payment-promotion-row';

const PAYMENT_METHOD_PROMOTIONS = [
	{
		gatewayId: 'pre_install_woocommerce_payments_promotion',
		pluginSlug: 'woocommerce-payments',
		link: 'https://woocommerce.com/payments/?utm_medium=product',
	},
];

PAYMENT_METHOD_PROMOTIONS.forEach( ( paymentMethod ) => {
	const container = document.querySelector(
		`[data-gateway_id="${ paymentMethod.gatewayId }"]`
	);

	if ( container ) {
		const sortColumn = container.children[ 0 ].innerHTML;
		const descriptionColumn = container.children[ 3 ].innerHTML;
		const title = container.getElementsByClassName(
			'wc-payment-gateway-method-title'
		);
		const subTitle = container.getElementsByClassName( 'gateway-subtitle' );

		render(
			<PaymentPromotionRow
				pluginSlug={ paymentMethod.pluginSlug }
				sortColumnContent={ sortColumn }
				descriptionColumnContent={ descriptionColumn }
				title={ title.length === 1 ? title[ 0 ].innerHTML : undefined }
				titleLink={ paymentMethod.link }
				subTitleContent={
					subTitle.length === 1 ? subTitle[ 0 ].innerHTML : undefined
				}
			/>,
			container
		);
	}
} );
