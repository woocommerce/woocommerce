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
import { getAdminLink } from '@woocommerce/settings';
import { Button } from '@wordpress/components';
import ExternalIcon from 'gridicons/dist/external';

/**
 * Internal dependencies
 */
import { List, Placeholder as ListPlaceholder } from './components/List';
import { Setup, Placeholder as SetupPlaceholder } from './components/Setup';
import { Toggle } from './components/Toggle/Toggle';
import { WCPaySuggestion } from './components/WCPay';
import { getPluginSlug } from '~/utils';
import { getCountryCode } from '~/dashboard/utils';
import './plugins/Bacs';
import './payment-gateway-suggestions.scss';

const comparePaymentGatewaysByPriority = ( a, b ) =>
	a.recommendation_priority - b.recommendation_priority;

const isGatewayWCPay = ( gateway ) =>
	gateway.plugins?.length === 1 &&
	gateway.plugins[ 0 ] === 'woocommerce-payments';

const isGatewayOtherCategory = ( gateway, countryCode ) =>
	gateway.category_other &&
	gateway.category_other.indexOf( countryCode ) !== -1;

const isGatewayAdditionalCategory = ( gateway, countryCode ) =>
	gateway.category_additional &&
	gateway.category_additional.indexOf( countryCode ) !== -1;

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
			).getPaymentGatewaySuggestions(),
			countryCode: getCountryCode( settings.woocommerce_default_country ),
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
				hasPlugins: !! (
					suggestion.plugins && suggestion.plugins.length
				),
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

	const isWCPayOrOtherCategoryDoneSetup = useMemo( () => {
		for ( const [ , gateway ] of paymentGateways.entries() ) {
			if ( ! gateway.installed || gateway.needsSetup ) {
				continue;
			}

			if ( isGatewayWCPay( gateway ) ) {
				return true;
			}

			if ( isGatewayOtherCategory( gateway, countryCode ) ) {
				return true;
			}
		}
		return false;
	}, [ countryCode, paymentGateways ] );

	const isWCPaySupported =
		Array.from( paymentGateways.values() ).findIndex( isGatewayWCPay ) !==
		-1;

	const [ wcPayGateway, offlineGateways, additionalGateways ] = useMemo(
		() =>
			Array.from( paymentGateways.values() )
				.sort( ( a, b ) => {
					if ( a.hasPlugins === b.hasPlugins ) {
						return comparePaymentGatewaysByPriority( a, b );
					}

					// hasPlugins payment first
					if ( a.hasPlugins ) {
						return -1;
					}

					return 1;
				} )
				.reduce(
					( all, gateway ) => {
						const [ wcPay, offline, additional ] = all;

						// WCPay is handled separately when not installed and configured
						if (
							isGatewayWCPay( gateway ) &&
							! ( gateway.installed && ! gateway.needsSetup )
						) {
							wcPay.push( gateway );
						} else if ( gateway.is_offline ) {
							offline.push( gateway );
						} else if ( gateway.enabled ) {
							// Enabled gateways should be ignored.
						} else if ( isWCPayOrOtherCategoryDoneSetup ) {
							// If WCPay or "other" gateway is enabled in an WCPay-eligible country, only
							// allow to list "additional" gateways.
							if (
								isGatewayAdditionalCategory(
									gateway,
									countryCode
								)
							) {
								additional.push( gateway );
							}
						} else if ( ! isWCPaySupported ) {
							// When WCPay-ineligible, just show all gateways.
							additional.push( gateway );
						} else if (
							isGatewayOtherCategory( gateway, countryCode )
						) {
							// When nothing is set up and eligible for WCPay, only show "other" gateways.
							additional.push( gateway );
						}

						return all;
					},
					[ [], [], [] ]
				),
		[
			countryCode,
			isWCPaySupported,
			isWCPayOrOtherCategoryDoneSetup,
			paymentGateways,
		]
	);

	const trackSeeMore = () => {
		recordEvent( 'tasklist_payment_see_more', {} );
	};

	const trackToggle = ( isShow ) => {
		recordEvent( 'tasklist_payment_show_toggle', {
			toggle: isShow ? 'hide' : 'show',
			payment_method_count:
				offlineGateways.length + additionalGateways.length,
		} );
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

	const additionalSection = !! additionalGateways.length && (
		<List
			heading={
				! wcPayGateway.length &&
				__( 'Choose a payment provider', 'woocommerce' )
			}
			recommendation={ recommendation }
			paymentGateways={ additionalGateways }
			markConfigured={ markConfigured }
			footerLink={
				! isWCPayOrOtherCategoryDoneSetup && (
					<Button
						href={ getAdminLink(
							'admin.php?page=wc-addons&section=payment-gateways'
						) }
						target="_blank"
						onClick={ trackSeeMore }
						isTertiary
					>
						{ __( 'See more', 'woocommerce' ) }
						<ExternalIcon size={ 18 } />
					</Button>
				)
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
					<Toggle
						heading={ __( 'Other payment methods', 'woocommerce' ) }
						onToggle={ trackToggle }
					>
						{ additionalSection }
						{ offlineSection }
					</Toggle>
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
