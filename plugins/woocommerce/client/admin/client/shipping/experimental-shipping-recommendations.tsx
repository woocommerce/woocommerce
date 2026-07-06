/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { useEffect, useRef } from '@wordpress/element';
import {
	pluginsStore,
	settingsStore,
	onboardingStore,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { getCountryCode } from '~/dashboard/utils';
import { useOptionDismiss } from '~/hooks/use-option-dismiss';
import WooCommerceShippingItem from './experimental-woocommerce-shipping-item';
import ShipStationItem from './shipstation-item';
import PacklinkItem from './packlink-item';
import {
	SHIPPING_RECOMMENDATIONS_DISMISS_OPTION,
	ShippingRecommendationsMarketplaceLink,
	ShippingRecommendationsList,
	useInstallPlugin,
} from './shipping-recommendations';
import './shipping-recommendations.scss';
import { ShippingTour } from '../guided-tours/shipping-tour';

type ExtensionId = 'woocommerce-shipping' | 'shipstation' | 'packlink';

const COUNTRY_EXTENSIONS_MAP: Record< string, ExtensionId[] > = {
	US: [ 'woocommerce-shipping', 'shipstation' ],
	CA: [ 'shipstation' ],
	FR: [ 'packlink' ],
	ES: [ 'packlink' ],
	IT: [ 'packlink' ],
	DE: [ 'packlink' ],
	GB: [ 'shipstation' ],
	NL: [ 'packlink' ],
	AT: [ 'packlink' ],
	BE: [ 'packlink' ],
	AU: [ 'shipstation' ],
	NZ: [ 'shipstation' ],
	IE: [ 'packlink' ],
	PT: [ 'packlink' ],
};

const EXTENSION_PLUGIN_SLUGS: Record< ExtensionId, string > = {
	'woocommerce-shipping': 'woocommerce-shipping',
	shipstation: 'woocommerce-shipstation-integration',
	packlink: 'packlink-pro-shipping',
};

const ShippingRecommendations = () => {
	const [ pluginsBeingSetup, , handleInstall, handleActivate ] =
		useInstallPlugin();
	const recommendationsDismissState = useOptionDismiss(
		SHIPPING_RECOMMENDATIONS_DISMISS_OPTION
	);
	const { isDismissed: isRecommendationsHidden } =
		recommendationsDismissState;

	const {
		installedPlugins,
		activePlugins,
		countryCode,
		isSellingDigitalProductsOnly,
	} = useSelect( ( select ) => {
		const settings = select( settingsStore ).getSettings( 'general' );

		const { getInstalledPlugins, getActivePlugins } =
			select( pluginsStore );

		const profileItems =
			select( onboardingStore ).getProfileItems()?.product_types;

		return {
			installedPlugins: getInstalledPlugins(),
			activePlugins: getActivePlugins(),
			countryCode: getCountryCode(
				settings.general?.woocommerce_default_country
			),
			isSellingDigitalProductsOnly:
				profileItems?.length === 1 && profileItems[ 0 ] === 'downloads',
		};
	}, [] );

	const normalizedCountry = countryCode ?? '';

	const extensionsForCountry =
		COUNTRY_EXTENSIONS_MAP[ normalizedCountry ] ?? [];

	// Render every country-mapped recommendation regardless of which partner
	// is already installed: the settings page is meant to surface alternatives
	// the merchant can evaluate and switch to. The onboarding wizard keeps a
	// narrower selection because installing every option there at once is not
	// desired during initial setup.
	const visibleExtensions = isSellingDigitalProductsOnly
		? []
		: extensionsForCountry;

	const hasVisibleExtensions = visibleExtensions.length > 0;
	const shouldTrackRecommendationsImpression =
		! isRecommendationsHidden && hasVisibleExtensions;

	const visiblePluginSlugs = visibleExtensions
		.map( ( ext ) => EXTENSION_PLUGIN_SLUGS[ ext ] )
		.join( ',' );

	const impressionFired = useRef( false );
	useEffect( () => {
		if (
			shouldTrackRecommendationsImpression &&
			! impressionFired.current
		) {
			recordEvent( 'shipping_partner_impression', {
				context: 'settings',
				country: normalizedCountry,
				plugins: visiblePluginSlugs,
			} );
			impressionFired.current = true;
		}
	}, [
		shouldTrackRecommendationsImpression,
		normalizedCountry,
		visiblePluginSlugs,
	] );

	if ( ! hasVisibleExtensions ) {
		return (
			<>
				<ShippingTour showShippingRecommendationsStep={ false } />
				<ShippingRecommendationsMarketplaceLink
					textProps={ {
						as: 'p',
						className:
							'woocommerce-recommended-shipping__fallback-link',
					} }
				/>
			</>
		);
	}

	return (
		<div style={ { paddingBottom: 60 } }>
			<ShippingTour showShippingRecommendationsStep={ true } />
			<ShippingRecommendationsList
				dismissState={ recommendationsDismissState }
			>
				{ visibleExtensions.map( ( ext ) => {
					const isPluginInstalled = installedPlugins.includes(
						EXTENSION_PLUGIN_SLUGS[ ext ]
					);
					const isPluginActive = activePlugins.includes(
						EXTENSION_PLUGIN_SLUGS[ ext ]
					);
					const trackingProps = {
						context: 'settings' as const,
						country: normalizedCountry,
						plugins: visiblePluginSlugs,
					};
					switch ( ext ) {
						case 'woocommerce-shipping':
							return (
								<WooCommerceShippingItem
									key={ ext }
									isPluginInstalled={ isPluginInstalled }
									isPluginActive={ isPluginActive }
									pluginsBeingSetup={ pluginsBeingSetup }
									onInstallClick={ handleInstall }
									onActivateClick={ handleActivate }
									tracking={ trackingProps }
								/>
							);
						case 'shipstation':
							return (
								<ShipStationItem
									key={ ext }
									isPluginInstalled={ isPluginInstalled }
									isPluginActive={ isPluginActive }
									pluginsBeingSetup={ pluginsBeingSetup }
									onInstallClick={ handleInstall }
									onActivateClick={ handleActivate }
									tracking={ trackingProps }
								/>
							);
						case 'packlink':
							return (
								<PacklinkItem
									key={ ext }
									isPluginInstalled={ isPluginInstalled }
									isPluginActive={ isPluginActive }
									pluginsBeingSetup={ pluginsBeingSetup }
									onInstallClick={ handleInstall }
									onActivateClick={ handleActivate }
									tracking={ trackingProps }
								/>
							);
						default:
							return null;
					}
				} ) }
			</ShippingRecommendationsList>
			{ isRecommendationsHidden && (
				<ShippingRecommendationsMarketplaceLink
					textProps={ {
						as: 'p',
						className:
							'woocommerce-recommended-shipping__fallback-link',
					} }
				/>
			) }
		</div>
	);
};

export default ShippingRecommendations;
