/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';
import {
	OPTIONS_STORE_NAME,
	ONBOARDING_STORE_NAME,
	PAYMENT_GATEWAYS_STORE_NAME,
	SETTINGS_STORE_NAME,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { useMemo, useCallback, useEffect } from '@wordpress/element';
import { registerPlugin } from '@wordpress/plugins';
import { WooOnboardingTask } from '@woocommerce/onboarding';
import { getNewPath } from '@woocommerce/navigation';
import { Button } from '@wordpress/components';
import ExternalIcon from 'gridicons/dist/external';

/**
 * Internal dependencies
 */
import { List, Placeholder as ListPlaceholder } from './components/List';
import { Setup, Placeholder as SetupPlaceholder } from './components/Setup';
import { WCPaySuggestion } from './components/WCPay';
import { getCountryCode } from '~/dashboard/utils';
import {
	getEnrichedPaymentGateways,
	getSplitGateways,
	getIsWCPayOrOtherCategoryDoneSetup,
	getIsGatewayWCPay,
	comparePaymentGatewaysByPriority,
} from './utils';
import './plugins/Bacs';
import './payment-gateway-suggestions.scss';

export const PaymentGatewaySuggestions = ( { onComplete, query } ) => {
	const { updatePaymentGateway } = useDispatch( PAYMENT_GATEWAYS_STORE_NAME );
	const {
		getPaymentGateway,
		paymentGatewaySuggestions,
		installedPaymentGateways,
		isResolving,
		countryCode,
	} = useSelect( ( select ) => {
		const { getSettings } = select( SETTINGS_STORE_NAME );
		const { general: settings = {} } = getSettings( 'general' );
		return {
			getPaymentGateway: select( PAYMENT_GATEWAYS_STORE_NAME )
				.getPaymentGateway,
			getOption: select( OPTIONS_STORE_NAME ).getOption,
			installedPaymentGateways: select(
				PAYMENT_GATEWAYS_STORE_NAME
			).getPaymentGateways(),
			isResolving: select( ONBOARDING_STORE_NAME ).isResolving(
				'getPaymentGatewaySuggestions'
			),
			paymentGatewaySuggestions: select(
				ONBOARDING_STORE_NAME
			).getPaymentGatewaySuggestions( true ),
			countryCode: getCountryCode( settings.woocommerce_default_country ),
		};
	}, [] );

	const paymentGateways = useMemo(
		() =>
			getEnrichedPaymentGateways(
				installedPaymentGateways,
				paymentGatewaySuggestions
			),
		[ installedPaymentGateways, paymentGatewaySuggestions ]
	);

	useEffect( () => {
		if ( paymentGateways.size ) {
			recordEvent( 'tasklist_payments_options', {
				options: Array.from( paymentGateways.values() ).map(
					( gateway ) => gateway.id
				),
			} );
		}
	}, [ paymentGateways ] );

	const enablePaymentGateway = ( id ) => {
		if ( ! id ) {
			return;
		}

		const gateway = getPaymentGateway( id );

		if ( ! gateway ) {
			return;
		}

		updatePaymentGateway( id, {
			enabled: true,
		} ).then( () => {
			onComplete(
				// use the paymentGateways variable.
				// gateway variable doesn't have hasPlugins property.
				! paymentGateways.get( id )?.hasPlugins
					? {
							redirectPath: getNewPath(
								{ task: 'payments' },
								{},
								'/'
							),
					  }
					: {}
			);
		} );
	};

	const markConfigured = useCallback(
		async ( id ) => {
			if ( ! paymentGateways.get( id ) ) {
				throw `Payment gateway ${ id } not found in available gateways list`;
			}

			recordEvent( 'tasklist_payment_connect_method', {
				payment_method: id,
			} );

			enablePaymentGateway( id );
		},
		[ paymentGateways ]
	);

	const recommendation = useMemo(
		() =>
			Array.from( paymentGateways.values() )
				.filter( ( gateway ) => gateway.recommendation_priority )
				.sort( comparePaymentGatewaysByPriority )
				.map( ( gateway ) => gateway.id )
				.shift(),
		[ paymentGateways ]
	);

	const currentGateway = useMemo( () => {
		if ( ! query.id || isResolving || ! paymentGateways.size ) {
			return null;
		}

		const gateway = paymentGateways.get( query.id );

		if ( ! gateway ) {
			throw `Current gateway ${ query.id } not found in available gateways list`;
		}

		return gateway;
	}, [ isResolving, query, paymentGateways ] );

	const isWCPayOrOtherCategoryDoneSetup = useMemo(
		() =>
			getIsWCPayOrOtherCategoryDoneSetup( paymentGateways, countryCode ),
		[ countryCode, paymentGateways ]
	);

	const isWCPaySupported =
		Array.from( paymentGateways.values() ).findIndex(
			getIsGatewayWCPay
		) !== -1;

	const [ wcPayGateway, offlineGateways, additionalGateways ] = useMemo(
		() =>
			getSplitGateways(
				paymentGateways,
				countryCode,
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

	const trackSeeMore = () => {
		recordEvent( 'tasklist_payment_see_more', {} );
	};

	if ( query.id && ! currentGateway ) {
		return <SetupPlaceholder />;
	}

	if ( currentGateway ) {
		return (
			<Setup
				paymentGateway={ currentGateway }
				markConfigured={ markConfigured }
			/>
		);
	}

	let additionalSectionHeading = __(
		'Choose a payment provider',
		'woocommerce'
	);
	let additionalSectionHeadingDescription = __(
		'To start accepting online payments',
		'woocommerce'
	);
	if ( isWCPayOrOtherCategoryDoneSetup ) {
		additionalSectionHeading = __(
			'Additional payment options',
			'woocommerce'
		);
		additionalSectionHeadingDescription = __(
			'Give your customers additional choices in ways to pay.',
			'woocommerce'
		);
	} else if ( isWCPaySupported ) {
		additionalSectionHeading = __(
			'Other payment providers',
			'woocommerce'
		);
		additionalSectionHeadingDescription = __(
			'Try one of the alternative payment providers.',
			'woocommerce'
		);
	}

	const additionalSection = !! additionalGateways.length && (
		<List
			heading={ additionalSectionHeading }
			headingDescription={ additionalSectionHeadingDescription }
			recommendation={ recommendation }
			paymentGateways={ additionalGateways }
			markConfigured={ markConfigured }
			footerLink={
				<Button
					href="https://woo.com/product-category/woocommerce-extensions/payment-gateways/?utm_source=payments_recommendations"
					target="_blank"
					onClick={ trackSeeMore }
					isTertiary
				>
					{ __( 'See more', 'woocommerce' ) }
					<ExternalIcon size={ 18 } />
				</Button>
			}
		></List>
	);

	const offlineSection = !! offlineGateways.length && (
		<List
			heading={ __( 'Offline payment methods', 'woocommerce' ) }
			recommendation={ recommendation }
			paymentGateways={ offlineGateways }
			markConfigured={ markConfigured }
		/>
	);

	return (
		<div className="woocommerce-task-payments">
			{ ! paymentGateways.size && <ListPlaceholder /> }

			{ wcPayGateway.length ? (
				<>
					<WCPaySuggestion paymentGateway={ wcPayGateway[ 0 ] } />
					{ additionalSection }
					{ offlineSection }
				</>
			) : (
				<>
					{ additionalSection }
					{ offlineSection }
				</>
			) }
		</div>
	);
};

registerPlugin( 'wc-admin-onboarding-task-payments', {
	scope: 'woocommerce-tasks',
	render: () => (
		<WooOnboardingTask id="payments">
			{ ( { onComplete, query } ) => (
				<PaymentGatewaySuggestions
					onComplete={ onComplete }
					query={ query }
				/>
			) }
		</WooOnboardingTask>
	),
} );
