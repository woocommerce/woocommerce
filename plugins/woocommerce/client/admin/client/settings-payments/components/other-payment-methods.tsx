/**
 * External dependencies
 */
import React, { useMemo } from '@wordpress/element';
import { Button } from '@wordpress/components';
import ExternalIcon from 'gridicons/dist/external';
import { __, _x } from '@wordpress/i18n';
import {
	ONBOARDING_STORE_NAME,
	PAYMENT_GATEWAYS_STORE_NAME,
	SETTINGS_STORE_NAME,
} from '@woocommerce/data';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { getCountryCode } from '~/dashboard/utils';
import {
	getEnrichedPaymentGateways,
	getIsGatewayWCPay,
	getIsWCPayOrOtherCategoryDoneSetup,
	getSplitGateways,
} from '~/task-lists/fills/PaymentGatewaySuggestions/utils';

type PaymentGateway = {
	id: string;
	image_72x72: string;
	title: string;
	enabled: boolean;
	needsSetup: boolean;
	// Add other properties as needed...
};

const usePaymentGatewayData = () => {
	return useSelect( ( select ) => {
		const { getSettings } = select( SETTINGS_STORE_NAME );
		const { general: settings = {} } = getSettings( 'general' );
		return {
			getPaymentGateway: select( PAYMENT_GATEWAYS_STORE_NAME )
				.getPaymentGateway,
			installedPaymentGateways: select(
				PAYMENT_GATEWAYS_STORE_NAME
			).getPaymentGateways(),
			isResolving: select( ONBOARDING_STORE_NAME ).isResolving(
				'getPaymentGatewaySuggestions'
			),
			paymentGatewaySuggestions: select(
				ONBOARDING_STORE_NAME
			).getPaymentGatewaySuggestions(),
			countryCode: getCountryCode( settings.woocommerce_default_country ),
		};
	}, [] );
};

const AdditionalGatewayImages = ( {
	additionalGateways,
}: {
	additionalGateways: PaymentGateway[];
} ) => (
	<>
		{ additionalGateways.map( ( gateway ) => (
			<img
				key={ gateway.id }
				src={ gateway.image_72x72 }
				alt={ gateway.title }
				width="24"
				height="24"
				className="other-payment-methods__image"
			/>
		) ) }
		{ _x( '& more.', 'More payment providers to discover', 'woocommerce' ) }
	</>
);

export const OtherPaymentMethods = () => {
	const {
		paymentGatewaySuggestions,
		installedPaymentGateways,
		isResolving,
		countryCode,
	} = usePaymentGatewayData();

	const paymentGateways = useMemo(
		() =>
			getEnrichedPaymentGateways(
				installedPaymentGateways,
				paymentGatewaySuggestions
			),
		[ installedPaymentGateways, paymentGatewaySuggestions ]
	);

	const isWCPayOrOtherCategoryDoneSetup = useMemo(
		() =>
			getIsWCPayOrOtherCategoryDoneSetup( paymentGateways, countryCode ),
		[ countryCode, paymentGateways ]
	);

	const isWCPaySupported = Array.from( paymentGateways.values() ).some(
		getIsGatewayWCPay
	);

	// eslint-disable-next-line no-unused-vars, @typescript-eslint/no-unused-vars
	const [ wcPayGateway, _offlineGateways, additionalGateways ] = useMemo(
		() =>
			getSplitGateways(
				paymentGateways,
				countryCode ?? '',
				isWCPaySupported,
				isWCPayOrOtherCategoryDoneSetup
			),
		[
			paymentGateways,
			countryCode,
			isWCPaySupported,
			isWCPayOrOtherCategoryDoneSetup,
		]
	);

	if ( isResolving || ! wcPayGateway ) {
		return null;
	}

	const hasWcPaySetup = wcPayGateway.enabled && ! wcPayGateway.needsSetup;

	return (
		<>
			<Button
				className="is-tertiary"
				href="https://woocommerce.com/product-category/woocommerce-extensions/payment-gateways/?utm_source=payments_recommendations"
				target="_blank"
				value="tertiary"
				rel="noreferrer"
			>
				<span className="other-payment-methods__button-text">
					{ hasWcPaySetup
						? __(
								'Discover additional payment providers',
								'woocommerce'
						  )
						: __(
								'Discover other payment providers',
								'woocommerce'
						  ) }
				</span>
				<ExternalIcon size={ 18 } />
			</Button>
			{ additionalGateways.length > 0 && (
				<AdditionalGatewayImages
					additionalGateways={ additionalGateways }
				/>
			) }
		</>
	);
};
