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

/**
 * Splits up gateways to WCPay, offline and main list.
 *
 * @param {Array}   paymentGateways                 Payment gateway list.
 * @param {string}  countryCode                     Store country code.
 * @param {boolean} isWCPaySupported                Whether WCPay is supported in the store.
 * @param {boolean} isWCPayOrOtherCategoryDoneSetup Whether WCPay or "other" category gateway is done setup.
 * @return {Array} Array of [ WCPay, offline, main list, WCPayBNPL ].
 */
export const getSplitGateways = (
	paymentGateways,
	countryCode,
	isWCPaySupported,
	isWCPayOrOtherCategoryDoneSetup
) =>
	Array.from( paymentGateways.values() )
		.sort( comparePaymentGatewaysByPriority )
		.reduce(
			( all, gateway ) => {
				// mainList is the list of gateways that is shown in the payments task.
				const [ wcPay, offline, mainList, wcPayBnpl ] = all;

				// Handle WCPay-related gateways.
				if ( getIsGatewayWCPay( gateway ) ) {
					if ( isWCPaySupported ) {
						// If we encounter the special WCPay BNPL gateway, we handle it separately and
						// not let it be added to the regular WCPay list.
						if ( gateway.id === 'woocommerce_payments:bnpl' ) {
							// WCPay BNPL is only shown when WCPay is installed and setup.
							// It should be mutually exclusive with WCPay.
							if ( gateway.installed && ! gateway.needsSetup ) {
								wcPayBnpl.push( gateway );
							}
						} else if (
							! ( gateway.installed && ! gateway.needsSetup )
						) {
							// WCPay is always shown when it's not installed, or it's installed but needs setup.
							wcPay.push( gateway );
						}
					}

					// The WCPay-related gateway is ignored if it reaches here.
				} else if ( gateway.is_offline ) {
					// Offline gateways are always shown.
					offline.push( gateway );
				} else if ( gateway.enabled ) {
					// Enabled gateways should be ignored.
				} else if ( isWCPayOrOtherCategoryDoneSetup ) {
					if (
						getIsGatewayAdditionalCategory( gateway, countryCode )
					) {
						// If WCPay or "other" gateway is enabled, only
						// allow to list "additional" gateways.
						mainList.push( gateway );
					}
					// "other" gateways would be ignored here since we shouldn't promote competing gateways.
				} else if (
					getIsGatewayOtherCategory( gateway, countryCode )
				) {
					// When no WCPay or "other" gateway is enabled.
					mainList.push( gateway );
				}

				return all;
			},
			[ [], [], [], [] ]
		);
