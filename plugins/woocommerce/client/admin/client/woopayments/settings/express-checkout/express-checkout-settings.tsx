/**
 * External dependencies
 */
import { Notice, Spinner } from '@wordpress/components';
import { lazy, Suspense } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getSettingsPaymentsProviderRouteUrl } from '../../admin/utils';
import { ExpressCheckoutBusyState, ExpressCheckoutSaveBar } from './components';
import {
	asSettingsRecord,
	isAmazonPayExpressCheckoutAvailable,
	isWooPayExpressCheckoutAvailable,
} from './settings-utils';
import { useGetSettings, useSettings } from '../data/hooks';
import './style.scss';

type ExpressCheckoutMethodId = 'woopay' | 'payment_request' | 'amazon_pay';

const METHOD_TITLES: Record< ExpressCheckoutMethodId, string > = {
	woopay: 'WooPay',
	payment_request: 'Apple Pay / Google Pay',
	amazon_pay: 'Amazon Pay',
};

const METHOD_COMPONENTS = {
	woopay: lazy( () =>
		import(
			/* webpackChunkName: "settings-payments-woopayments-express-checkout-woopay" */ './woopay-settings'
		).then( ( module ) => ( { default: module.WooPaySettings } ) )
	),
	payment_request: lazy( () =>
		import(
			/* webpackChunkName: "settings-payments-woopayments-express-checkout-payment-request" */ './payment-request-settings'
		).then( ( module ) => ( { default: module.PaymentRequestSettings } ) )
	),
	amazon_pay: lazy( () =>
		import(
			/* webpackChunkName: "settings-payments-woopayments-express-checkout-amazon-pay" */ './amazon-pay-settings'
		).then( ( module ) => ( { default: module.AmazonPaySettings } ) )
	),
};

const isExpressCheckoutMethodId = (
	methodId: string
): methodId is ExpressCheckoutMethodId =>
	[ 'woopay', 'payment_request', 'amazon_pay' ].includes( methodId );

const isExpressCheckoutMethodAvailable = (
	methodId: ExpressCheckoutMethodId,
	settings: Record< string, unknown >
) => {
	if ( methodId === 'woopay' ) {
		return isWooPayExpressCheckoutAvailable( settings );
	}

	if ( methodId === 'amazon_pay' ) {
		return isAmazonPayExpressCheckoutAvailable( settings );
	}

	return true;
};

const getExpressCheckoutMethodUnavailableMessage = (
	methodId: ExpressCheckoutMethodId
) => {
	if ( methodId === 'woopay' ) {
		return __( 'WooPay is not available for this store.', 'woocommerce' );
	}

	return __( 'Amazon Pay is not available for this store.', 'woocommerce' );
};

export const WooPaymentsExpressCheckoutSettings = ( {
	methodId,
}: {
	methodId: string;
} ) => {
	const { isLoading, isSaving } = useSettings();
	const settings = asSettingsRecord( useGetSettings() );
	const hasSettings = Object.keys( settings ).length > 0;

	if ( ! isExpressCheckoutMethodId( methodId ) ) {
		return (
			<p>
				{ __(
					'Invalid express checkout method ID specified.',
					'woocommerce'
				) }
			</p>
		);
	}

	const MethodSettings = METHOD_COMPONENTS[ methodId ];
	const title = METHOD_TITLES[ methodId ];
	const headingId = `woopayments-express-checkout-settings-${ methodId }`;
	let content;

	if ( isLoading ) {
		content = (
			<p
				className="woopayments-express-checkout-settings__loading"
				aria-live="polite"
			>
				<Spinner />
				{ __( 'Loading WooPayments settings…', 'woocommerce' ) }
			</p>
		);
	} else if ( ! hasSettings ) {
		content = (
			<Notice status="error" isDismissible={ false }>
				{ __( 'Unable to load WooPayments settings.', 'woocommerce' ) }
			</Notice>
		);
	} else if ( ! isExpressCheckoutMethodAvailable( methodId, settings ) ) {
		content = (
			<Notice status="warning" isDismissible={ false }>
				{ getExpressCheckoutMethodUnavailableMessage( methodId ) }
			</Notice>
		);
	} else {
		content = (
			<ExpressCheckoutBusyState isBusy={ Boolean( isSaving ) }>
				<Suspense
					fallback={
						<p
							className="woopayments-express-checkout-settings__loading"
							aria-live="polite"
						>
							<Spinner />
							{ __(
								'Loading WooPayments settings…',
								'woocommerce'
							) }
						</p>
					}
				>
					<MethodSettings />
					<ExpressCheckoutSaveBar />
				</Suspense>
			</ExpressCheckoutBusyState>
		);
	}

	return (
		<section
			className="woopayments-express-checkout-settings"
			aria-labelledby={ headingId }
		>
			<header className="woopayments-express-checkout-settings__header">
				<a
					className="woopayments-express-checkout-settings__return-link"
					href={ getSettingsPaymentsProviderRouteUrl(
						'/woopayments/settings?from=woopayments-settings'
					) }
				>
					{ __( 'Return to payments', 'woocommerce' ) }
				</a>
				<h1 id={ headingId }>{ title }</h1>
			</header>
			{ content }
		</section>
	);
};
