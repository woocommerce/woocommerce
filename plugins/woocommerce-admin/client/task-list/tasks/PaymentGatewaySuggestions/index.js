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
import { List, Placeholder as ListPlaceholder } from './components/List';
import { Setup, Placeholder as SetupPlaceholder } from './components/Setup';
import { WCPaySuggestion } from './components/WCPay';
import './plugins/Bacs';

export const PaymentGatewaySuggestions = ( { query } ) => {
	const { invalidateResolutionForStoreSelector } = useDispatch(
		ONBOARDING_STORE_NAME
	);
	const { updatePaymentGateway } = useDispatch( PAYMENT_GATEWAYS_STORE_NAME );
	const { getPaymentGateway, paymentGateways, isResolving } = useSelect(
		( select ) => {
			const installedPaymentGateways = select(
				PAYMENT_GATEWAYS_STORE_NAME
			)
				.getPaymentGateways()
				.reduce( ( map, gateway ) => {
					map[ gateway.id ] = gateway;
					return map;
				}, {} );

			const mappedSuggestions = select( ONBOARDING_STORE_NAME )
				.getPaymentGatewaySuggestions()
				.reduce( ( map, suggestion ) => {
					const { id } = suggestion;
					const installedGateway = installedPaymentGateways[
						suggestion.id
					]
						? installedPaymentGateways[ id ]
						: {};

					const enrichedSuggestion = {
						installed: !! installedPaymentGateways[ id ],
						postInstallScripts:
							installedGateway.post_install_scripts,
						enabled: installedGateway.enabled || false,
						needsSetup: installedGateway.needs_setup,
						settingsUrl: installedGateway.settings_url,
						connectionUrl: installedGateway.connection_url,
						setupHelpText: installedGateway.setup_help_text,
						title: installedGateway.title,
						requiredSettings: installedGateway.required_settings_keys
							? installedGateway.required_settings_keys
									.map(
										( settingKey ) =>
											installedGateway.settings[
												settingKey
											]
									)
									.filter( Boolean )
							: [],
						...suggestion,
					};

					map.set( id, enrichedSuggestion );
					return map;
				}, new Map() );

			return {
				getPaymentGateway: select( PAYMENT_GATEWAYS_STORE_NAME )
					.getPaymentGateway,
				getOption: select( OPTIONS_STORE_NAME ).getOption,
				isResolving: select( ONBOARDING_STORE_NAME ).isResolving(
					'getPaymentGatewaySuggestions'
				),
				paymentGateways: mappedSuggestions,
			};
		}
	);

	const enablePaymentGateway = ( id ) => {
		if ( ! id ) {
			return;
		}

		const gateway = getPaymentGateway( id );

		if ( ! gateway || gateway.enabled ) {
			return;
		}

		updatePaymentGateway( id, {
			enabled: true,
		} ).then( () => {
			invalidateResolutionForStoreSelector( 'getTasksStatus' );
		} );
	};

	const markConfigured = useCallback(
		async ( id, queryParams = {} ) => {
			if ( ! paymentGateways.get( id ) ) {
				throw `Payment gateway ${ id } not found in available gateways list`;
			}

			enablePaymentGateway( id );

			recordEvent( 'tasklist_payment_connect_method', {
				payment_method: id,
			} );

			getHistory().push( getNewPath( { ...queryParams }, '/', {} ) );
		},
		[ paymentGateways ]
	);

	const recordConnectStartEvent = useCallback( ( gatewayId ) => {
		recordEvent( 'tasklist_payment_connect_start', {
			payment_method: gatewayId,
		} );
	}, [] );

	const recommendation = useMemo(
		() =>
			Array.from( paymentGateways.values() )
				.filter( ( gateway ) => gateway.recommendation_priority )
				.sort(
					( a, b ) =>
						a.recommendation_priority - b.recommendation_priority
				)
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

	const [ wcPayGateway, enabledGateways, additionalGateways ] = useMemo(
		() =>
			Array.from( paymentGateways.values() ).reduce(
				( all, gateway ) => {
					const [ wcPay, enabled, additional ] = all;

					// WCPay is handled separately when not installed and configured
					if (
						gateway.id === 'woocommerce_payments' &&
						! ( gateway.installed && ! gateway.needsSetup )
					) {
						wcPay.push( gateway );
					} else if ( gateway.enabled ) {
						enabled.push( gateway );
					} else {
						additional.push( gateway );
					}

					return all;
				},
				[ [], [], [] ]
			),
		[ paymentGateways ]
	);

	if ( query.id && ! currentGateway ) {
		return <SetupPlaceholder />;
	}

	if ( currentGateway ) {
		return (
			<Setup
				paymentGateway={ currentGateway }
				markConfigured={ markConfigured }
				recordConnectStartEvent={ recordConnectStartEvent }
			/>
		);
	}

	return (
		<div className="woocommerce-task-payments">
			{ ! paymentGateways.size && <ListPlaceholder /> }

			{ !! wcPayGateway.length && (
				<WCPaySuggestion paymentGateway={ wcPayGateway[ 0 ] } />
			) }

			{ !! enabledGateways.length && (
				<List
					heading={ __(
						'Enabled payment gateways',
						'woocommerce-admin'
					) }
					recommendation={ recommendation }
					paymentGateways={ enabledGateways }
				/>
			) }

			{ !! additionalGateways.length && (
				<List
					heading={ __(
						'Additional payment gateways',
						'woocommerce-admin'
					) }
					recommendation={ recommendation }
					paymentGateways={ additionalGateways }
					markConfigured={ markConfigured }
				/>
			) }
		</div>
	);
};

export default PaymentGatewaySuggestions;
