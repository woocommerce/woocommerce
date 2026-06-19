/**
 * Internal dependencies
 */
import { WC_ASSET_URL } from '~/utils/admin-settings';
import WooPayLogoImage from './assets/woopay-preview-logo.svg';

type ExpressCheckoutMethod = 'woopay' | 'payment_request' | 'amazon_pay';

const assetUrl = ( path: string ) => `${ WC_ASSET_URL || '' }${ path }`;

const METHOD_ICONS: Record<
	ExpressCheckoutMethod,
	Array< { alt: string; src: string } >
> = {
	woopay: [ { alt: 'WooPay', src: WooPayLogoImage } ],
	payment_request: [
		{
			alt: 'Apple Pay',
			src: assetUrl( 'images/payment-methods/applepay.svg' ),
		},
		{
			alt: 'Google Pay',
			src: assetUrl( 'images/payment-methods/googlepay.svg' ),
		},
	],
	amazon_pay: [
		{
			alt: 'Amazon Pay',
			src: assetUrl( 'images/payment_methods/72x72/amazonpay.png' ),
		},
	],
};

export const ExpressCheckoutMethodIcons = ( {
	methodId,
}: {
	methodId: ExpressCheckoutMethod;
} ) => (
	<div className="woopayments-express-checkout-settings__icons">
		{ METHOD_ICONS[ methodId ].map( ( icon ) => (
			<div
				className="woopayments-express-checkout-settings__icon"
				key={ icon.alt }
			>
				<img src={ icon.src } alt={ icon.alt } />
			</div>
		) ) }
	</div>
);
