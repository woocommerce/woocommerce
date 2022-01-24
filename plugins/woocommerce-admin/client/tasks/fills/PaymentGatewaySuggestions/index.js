/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';
import {
	OPTIONS_STORE_NAME,
	ONBOARDING_STORE_NAME,
	PAYMENT_GATEWAYS_STORE_NAME,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { useMemo, useCallback, useEffect } from '@wordpress/element';
import { registerPlugin } from '@wordpress/plugins';
import { WooOnboardingTask } from '@woocommerce/onboarding';

/**
 * Internal dependencies
 */
import { List, Placeholder as ListPlaceholder } from './components/List';
import { Setup, Placeholder as SetupPlaceholder } from './components/Setup';
import { WCPaySuggestion } from './components/WCPay';
import { getPluginSlug } from '~/utils';
import './plugins/Bacs';
import './payment-gateway-suggestions.scss';

export const PaymentGatewaySuggestions = ( { onComplete, query } ) => {
	const { updatePaymentGateway } = useDispatch( PAYMENT_GATEWAYS_STORE_NAME );
	const {
		getPaymentGateway,
		paymentGatewaySuggestions,
		installedPaymentGateways,
		isResolving,
	} = useSelect( ( select ) => {
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
			).getPaymentGatewaySuggestions(),
		};
	}, [] );

	const getEnrichedPaymentGateways = () => {
		const mappedPaymentGateways = installedPaymentGateways.reduce(
			( map, gateway ) => {
				map[ gateway.id ] = gateway;
				return map;
			},
			{}
		);

		return paymentGatewaySuggestions.reduce( ( map, suggestion ) => {
			// A colon ':' is used sometimes to have multiple configs for the same gateway ex: woocommerce_payments:us.
			const id = getPluginSlug( suggestion.id );
			const installedGateway = mappedPaymentGateways[ id ]
				? mappedPaymentGateways[ id ]
				: {};

			const enrichedSuggestion = {
				installed: !! mappedPaymentGateways[ id ],
				postInstallScripts: installedGateway.post_install_scripts,
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
									installedGateway.settings[ settingKey ]
							)
							.filter( Boolean )
					: [],
				...suggestion,
			};

			map.set( suggestion.id, enrichedSuggestion );
			return map;
		}, new Map() );
	};

	const paymentGateways = useMemo( getEnrichedPaymentGateways, [
		installedPaymentGateways,
		paymentGatewaySuggestions,
	] );

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
			onComplete();
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
						gateway.plugins?.length === 1 &&
						gateway.plugins[ 0 ] === 'woocommerce-payments' &&
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
