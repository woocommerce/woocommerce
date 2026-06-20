/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { ExpressCheckoutInlineNotice } from './components';
import {
	asSettingsRecord,
	getExpressCheckoutFeatureFlags,
	isWooPayExpressCheckoutAvailable,
} from './settings-utils';
import {
	useAmazonPayEnabledSettings,
	useGetAvailablePaymentMethodIds,
	useGetSettings,
	usePaymentRequestEnabledSettings,
	useWooPayEnabledSettings,
} from '../data/hooks';

type ExpressCheckoutMethod = 'woopay' | 'payment_request' | 'amazon_pay';

const METHOD_LABELS: Record< ExpressCheckoutMethod, string > = {
	woopay: 'WooPay',
	payment_request: 'Apple Pay / Google Pay',
	amazon_pay: 'Amazon Pay',
};

const formatButtonList = ( buttonNames: string[] ) => {
	if ( buttonNames.length === 1 ) {
		return sprintf(
			/* translators: %s: name of a button type. */
			__( '%s button', 'woocommerce' ),
			buttonNames[ 0 ]
		);
	}

	if ( buttonNames.length === 2 ) {
		return sprintf(
			/* translators: %1$s and %2$s: names of button types. */
			__( '%1$s and %2$s buttons', 'woocommerce' ),
			buttonNames[ 0 ],
			buttonNames[ 1 ]
		);
	}

	return sprintf(
		/* translators: %1$s: comma-separated list of button types, %2$s: final button type. */
		__( '%1$s, and %2$s buttons', 'woocommerce' ),
		buttonNames.slice( 0, -1 ).join( ', ' ),
		buttonNames[ buttonNames.length - 1 ]
	);
};

export const ExpressCheckoutSettingsNotices = ( {
	currentMethod,
}: {
	currentMethod: ExpressCheckoutMethod;
} ) => {
	const [ isWooPayEnabled ] = useWooPayEnabledSettings() as [ boolean ];
	const [ isPaymentRequestEnabled ] = usePaymentRequestEnabledSettings() as [
		boolean
	];
	const [ isAmazonPayEnabled ] = useAmazonPayEnabledSettings() as [ boolean ];
	const settings = asSettingsRecord( useGetSettings() );
	const featureFlags = getExpressCheckoutFeatureFlags( settings );
	const availablePaymentMethodIds =
		useGetAvailablePaymentMethodIds() as string[];
	const isAmazonPayAvailable =
		availablePaymentMethodIds.includes( 'amazon_pay' );
	const isWooPayEffectivelyEnabled =
		isWooPayEnabled && isWooPayExpressCheckoutAvailable( settings );
	const isAmazonPayEffectivelyEnabled =
		isAmazonPayEnabled && featureFlags.amazonPay && isAmazonPayAvailable;
	const enabledMethods = [
		currentMethod !== 'woopay' &&
			isWooPayEffectivelyEnabled &&
			METHOD_LABELS.woopay,
		currentMethod !== 'payment_request' &&
			isPaymentRequestEnabled &&
			METHOD_LABELS.payment_request,
		currentMethod !== 'amazon_pay' &&
			isAmazonPayEffectivelyEnabled &&
			METHOD_LABELS.amazon_pay,
	].filter( Boolean ) as string[];

	if ( enabledMethods.length === 0 ) {
		return null;
	}

	return (
		<>
			<ExpressCheckoutInlineNotice>
				{ sprintf(
					/* translators: %s: formatted list of express checkout buttons. */
					__(
						'These settings will also apply to the %s on your store.',
						'woocommerce'
					),
					formatButtonList( enabledMethods )
				) }
			</ExpressCheckoutInlineNotice>
			<ExpressCheckoutInlineNotice>
				{ __(
					'Some appearance settings may be overridden in the express payment section of the Cart & Checkout blocks.',
					'woocommerce'
				) }
			</ExpressCheckoutInlineNotice>
		</>
	);
};
