/**
 * External dependencies
 */
import {
	registerExpressPaymentMethod,
	registerPaymentMethod,
} from '@woocommerce/blocks-registry';
import { __ } from '@wordpress/i18n';
import { getPaymentMethodData, WC_ASSET_URL } from '@woocommerce/settings';
import { decodeEntities } from '@wordpress/html-entities';
import { sanitizeHTML } from '@woocommerce/sanitize';
import { lazy, Suspense, RawHTML } from '@wordpress/element';
/**
 * Internal dependencies
 */
import { PAYMENT_METHOD_NAME } from './constants';

const settings = getPaymentMethodData( 'paypal', {} );

/**
 * Content component
 */
const Content = () => {
	return <RawHTML>{ sanitizeHTML( settings.description || '' ) }</RawHTML>;
};

const paypalPaymentMethod = {
	name: PAYMENT_METHOD_NAME,
	label: (
		<img
			src={ `${ WC_ASSET_URL }/images/paypal.png` }
			alt={ decodeEntities(
				settings.title || __( 'PayPal', 'woocommerce' )
			) }
		/>
	),
	placeOrderButtonLabel: __( 'Proceed to PayPal', 'woocommerce' ),
	content: <Content />,
	edit: <Content />,
	canMakePayment: () => true,
	ariaLabel: decodeEntities(
		settings?.title || __( 'Payment via PayPal', 'woocommerce' )
	),
	supports: {
		features: settings.supports ?? [],
	},
};

registerPaymentMethod( paypalPaymentMethod );

if ( settings.isButtonsEnabled ) {
	// Dynamically import the PayPal wrapper component
	const PayPalButtonsContainer = lazy( () => import( './buttons' ) );
	const LazyPayPalButtonsContainer = () => {
		const options = settings?.buttonsOptions;
		if ( ! options || ! options[ 'client-id' ] ) {
			return null;
		}

		const params = {
			clientId: options[ 'client-id' ],
			merchantId: options[ 'merchant-id' ],
			partnerAttributionId: options[ 'partner-attribution-id' ],
			components: options.components,
			disableFunding: options[ 'disable-funding' ],
			enableFunding: options[ 'enable-funding' ],
			currency: options.currency,
			intent: options.intent,
			pageType: options[ 'page-type' ],
			isProductPage: settings.isProductPage,
			appSwitchRequestOrigin: settings.appSwitchRequestOrigin,
		};

		return (
			<Suspense fallback={ null }>
				<PayPalButtonsContainer { ...params } />
			</Suspense>
		);
	};

	registerExpressPaymentMethod( {
		name: __( 'PayPal', 'woocommerce' ),
		title: __( 'PayPal', 'woocommerce' ),
		description: __( 'PayPal Buttons', 'woocommerce' ),
		gatewayId: 'paypal',
		paymentMethodId: 'paypal',
		content: <LazyPayPalButtonsContainer />,
		edit: <LazyPayPalButtonsContainer />,
		canMakePayment: () => true,
		supports: {
			features: [ 'products' ],
		},
	} );
}
