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
import WooCommerceShippingItem from './woocommerce-shipping-item';
import ShipStationItem from './shipstation-item';
import PacklinkItem from './packlink-item';
import {
	SHIPPING_RECOMMENDATIONS_DISMISS_OPTION,
	ShippingRecommendationsList,
	ShippingRecommendationsMarketplaceLink,
	useInstallPlugin,
} from './shipping-recommendations-utils';
import './shipping-recommendations.scss';
import { ShippingTour } from '~/guided-tours/shipping-tour';

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
	const [ pluginsBeingSetup, handleInstall, handleActivate ] =
		useInstallPlugin();
	const recommendationsDismissState = useOptionDismiss(
		SHIPPING_RECOMMENDATIONS_DISMISS_OPTION
	);
	const {
		isDismissed: isRecommendationsHidden,
		hasResolved: hasRecommendationsDismissResolved,
	} = recommendationsDismissState;

	const {
		installedPlugins,
		activePlugins,
		countryCode,
		isSellingDigitalProductsOnly,
		hasRecommendationEligibilityResolved,
	} = useSelect( ( select ) => {
		const {
			getSettings,
			hasFinishedResolution: hasSettingsFinishedResolution,
		} = select( settingsStore );
		const {
			getProfileItems,
			hasFinishedResolution: hasOnboardingFinishedResolution,
		} = select( onboardingStore );
		const settings = getSettings( 'general' );

		const { getInstalledPlugins, getActivePlugins } =
			select( pluginsStore );

		const profileItems = getProfileItems()?.product_types;

		return {
			installedPlugins: getInstalledPlugins(),
			activePlugins: getActivePlugins(),
			countryCode: getCountryCode(
				settings.general?.woocommerce_default_country
			),
			isSellingDigitalProductsOnly:
				profileItems?.length === 1 && profileItems[ 0 ] === 'downloads',
			hasRecommendationEligibilityResolved:
				hasSettingsFinishedResolution( 'getSettings', [ 'general' ] ) &&
				hasOnboardingFinishedResolution( 'getProfileItems', [] ),
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
	// Country and product type both determine which final state should render.
	// Wait for them to settle so the fallback and recommendations do not swap.
	const shouldWaitForRecommendationData =
		! hasRecommendationEligibilityResolved ||
		( ! hasRecommendationsDismissResolved && hasVisibleExtensions );
	const shouldShowRecommendationsFallback =
		( hasRecommendationsDismissResolved && isRecommendationsHidden ) ||
		! hasVisibleExtensions ||
		isSellingDigitalProductsOnly;
	const shouldTrackRecommendationsImpression =
		hasRecommendationEligibilityResolved &&
		hasRecommendationsDismissResolved &&
		! isRecommendationsHidden &&
		hasVisibleExtensions;

	const visiblePluginSlugs = visibleExtensions
		.map( ( ext ) => EXTENSION_PLUGIN_SLUGS[ ext ] )
		.join( ',' );
	const marketplaceFallbackLink = (
		<ShippingRecommendationsMarketplaceLink
			textProps={ {
				as: 'p',
				className: 'woocommerce-recommended-shipping__fallback-link',
			} }
		/>
	);

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

	if ( shouldWaitForRecommendationData ) {
		return (
			<>
				<ShippingTour showShippingRecommendationsStep={ false } />
			</>
		);
	}

	if ( shouldShowRecommendationsFallback ) {
		return (
			<>
				<ShippingTour showShippingRecommendationsStep={ false } />
				{ marketplaceFallbackLink }
			</>
		);
	}

	return (
		<>
			<ShippingTour
				showShippingRecommendationsStep={ ! isRecommendationsHidden }
			/>
			<div style={ { paddingBottom: 60 } }>
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
			</div>
		</>
	);
};

export default ShippingRecommendations;
