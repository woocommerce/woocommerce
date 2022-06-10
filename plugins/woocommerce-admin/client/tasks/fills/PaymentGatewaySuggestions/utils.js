/**
 * Internal dependencies
 */
import { getPluginSlug } from '~/utils';

export const comparePaymentGatewaysByPriority = ( a, b ) =>
	a.recommendation_priority - b.recommendation_priority;

export const getIsGatewayWCPay = ( gateway ) =>
	gateway.plugins?.length === 1 &&
	gateway.plugins[ 0 ] === 'woocommerce-payments';

export const getIsGatewayOtherCategory = ( gateway, countryCode ) =>
	gateway.category_other &&
	gateway.category_other.indexOf( countryCode ) !== -1;

export const getIsGatewayAdditionalCategory = ( gateway, countryCode ) =>
	gateway.category_additional &&
	gateway.category_additional.indexOf( countryCode ) !== -1;

export const getEnrichedPaymentGateways = (
	installedPaymentGateways,
	paymentGatewaySuggestions
) => {
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
			hasPlugins: !! ( suggestion.plugins && suggestion.plugins.length ),
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

export const getIsWCPayOrOtherCategoryDoneSetup = (
	paymentGateways,
	countryCode
) => {
	for ( const [ , gateway ] of paymentGateways.entries() ) {
		if ( ! gateway.installed || gateway.needsSetup ) {
			continue;
		}

		if ( getIsGatewayWCPay( gateway ) ) {
			return true;
		}

		if ( getIsGatewayOtherCategory( gateway, countryCode ) ) {
			return true;
		}
	}
	return false;
};

export const getSplitGateways = (
	paymentGateways,
	countryCode,
	isWCPaySupported,
	isWCPayOrOtherCategoryDoneSetup
) =>
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
					getIsGatewayWCPay( gateway ) &&
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
						getIsGatewayAdditionalCategory( gateway, countryCode )
					) {
						additional.push( gateway );
					}
				} else if ( ! isWCPaySupported ) {
					// When WCPay-ineligible, just show all gateways.
					additional.push( gateway );
				} else if (
					getIsGatewayOtherCategory( gateway, countryCode )
				) {
					// When nothing is set up and eligible for WCPay, only show "other" gateways.
					additional.push( gateway );
				}

				return all;
			},
			[ [], [], [] ]
		);
