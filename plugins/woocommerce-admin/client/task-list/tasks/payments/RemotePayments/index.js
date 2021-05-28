/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';
import { getHistory, getNewPath } from '@woocommerce/navigation';
import {
	OPTIONS_STORE_NAME,
	ONBOARDING_STORE_NAME,
	PAYMENT_GATEWAYS_STORE_NAME,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { useMemo, useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { RecommendedPaymentGatewayList } from './components/RecommendedPaymentGatewayList';
import { PaymentMethod } from './components/PaymentMethod';
import { WCPayMethodCard } from '../components/WCPayMethodCard';

const RECOMMENDED_GATEWAY_KEYS = [
	'woocommerce_payments',
	'mercadopago',
	'stripe',
];

export const RemotePayments = ( { query } ) => {
	const { updatePaymentGateway } = useDispatch( PAYMENT_GATEWAYS_STORE_NAME );
	const {
		additionalPaymentGatewayRecommendations,
		enabledPaymentGatewayRecommendations,
		getPaymentGateway,
		installedPaymentGateways,
		paymentGatewayRecommendations,
		isResolving,
	} = useSelect( ( select ) => {
		const paymentGateways = select( PAYMENT_GATEWAYS_STORE_NAME )
			.getPaymentGateways()
			.reduce( ( map, gateway ) => {
				map[ gateway.id ] = gateway;
				return map;
			}, {} );

		const enabled = new Map();
		const additional = new Map();
		const recommendations = select( ONBOARDING_STORE_NAME )
			.getPaymentMethodRecommendations()
			.reduce( ( map, gateway ) => {
				map.set( gateway.key, gateway );
				if (
					paymentGateways[ gateway.key ] &&
					paymentGateways[ gateway.key ].enabled
				) {
					enabled.set( gateway.key, gateway );
				} else {
					additional.set( gateway.key, gateway );
				}
				return map;
			}, new Map() );

		return {
			additionalPaymentGatewayRecommendations: additional,
			enabledPaymentGatewayRecommendations: enabled,
			getPaymentGateway: select( PAYMENT_GATEWAYS_STORE_NAME )
				.getPaymentGateway,
			getOption: select( OPTIONS_STORE_NAME ).getOption,
			installedPaymentGateways: paymentGateways,
			isResolving: select( ONBOARDING_STORE_NAME ).isResolving(
				'getPaymentMethodRecommendations'
			),
			paymentGatewayRecommendations: recommendations,
		};
	} );

	const recommendedMethod = useMemo( () => {
		for ( const key in RECOMMENDED_GATEWAY_KEYS ) {
			const gateway = paymentGatewayRecommendations.get( key );
			if ( gateway && gateway.visible ) {
				return gateway;
			}
		}
		return null;
	}, [ paymentGatewayRecommendations ] );

	const enablePaymentGateway = ( paymentGatewayKey ) => {
		if ( ! paymentGatewayKey ) {
			return;
		}

		const gateway = getPaymentGateway( paymentGatewayKey );

		if ( ! gateway || gateway.enabled ) {
			return;
		}

		updatePaymentGateway( paymentGatewayKey, {
			enabled: true,
		} );
	};

	const markConfigured = useCallback(
		async ( paymentGatewayKey, queryParams = {} ) => {
			if ( ! paymentGatewayRecommendations.get( paymentGatewayKey ) ) {
				throw `Method ${ paymentGatewayKey } not found in available methods list`;
			}

			enablePaymentGateway( paymentGatewayKey );

			recordEvent( 'tasklist_payment_connect_method', {
				payment_method: paymentGatewayKey,
			} );

			getHistory().push(
				getNewPath( { ...queryParams, task: 'payments' }, '/', {} )
			);
		},
		[ installedPaymentGateways, paymentGatewayRecommendations ]
	);

	const recordConnectStartEvent = useCallback( ( methodName ) => {
		recordEvent( 'tasklist_payment_connect_start', {
			payment_method: methodName,
		} );
	}, [] );

	const currentPaymentGateway = useMemo( () => {
		if (
			! query.method ||
			isResolving ||
			! paymentGatewayRecommendations.size
		) {
			return null;
		}

		const gateway = paymentGatewayRecommendations.get( query.method );

		if ( ! gateway ) {
			throw `Current method ${ query.method } not found in available methods list`;
		}

		return gateway;
	}, [ isResolving, query, paymentGatewayRecommendations ] );

	if ( currentPaymentGateway ) {
		return (
			<PaymentMethod
				method={ currentPaymentGateway }
				markConfigured={ markConfigured }
				recordConnectStartEvent={ recordConnectStartEvent }
			/>
		);
	}

	const wcPayGateway = additionalPaymentGatewayRecommendations.get(
		'woocommerce_payments'
	);

	if ( wcPayGateway ) {
		additionalPaymentGatewayRecommendations.delete(
			'woocommerce_payments'
		);
	}

	return (
		<div className="woocommerce-task-payments">
			{ !! wcPayGateway && (
				<WCPayMethodCard
					isEnabled={
						'woocommerce_payments' in installedPaymentGateways &&
						installedPaymentGateways.woocommerce_payments.enabled
					}
					method={ wcPayGateway }
				/>
			) }

			{ !! enabledPaymentGatewayRecommendations.size && (
				<RecommendedPaymentGatewayList
					recommendedMethod={ recommendedMethod }
					heading={ __( 'Enabled payment methods', 'wc-admin' ) }
					installedPaymentGateways={ installedPaymentGateways }
					recommendedPaymentGateways={ Array.from(
						enabledPaymentGatewayRecommendations.values()
					) }
				/>
			) }

			{ !! additionalPaymentGatewayRecommendations.size && (
				<RecommendedPaymentGatewayList
					recommendedMethod={ recommendedMethod }
					heading={ __( 'Additional payment methods', 'wc-admin' ) }
					installedPaymentGateways={ installedPaymentGateways }
					recommendedPaymentGateways={ Array.from(
						additionalPaymentGatewayRecommendations.values()
					) }
					markConfigured={ markConfigured }
				/>
			) }
		</div>
	);
};

export default RemotePayments;
