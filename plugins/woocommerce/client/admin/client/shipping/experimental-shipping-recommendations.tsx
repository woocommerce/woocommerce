/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import {
	pluginsStore,
	settingsStore,
	onboardingStore,
} from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { getCountryCode } from '~/dashboard/utils';
import WooCommerceShippingItem from './experimental-woocommerce-shipping-item';
import ShipStationItem from './shipstation-item';
import PacklinkItem from './packlink-item';
import {
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
	DE: [ 'shipstation', 'packlink' ],
	GB: [ 'shipstation', 'packlink' ],
	NL: [ 'packlink' ],
	AT: [ 'packlink' ],
	BE: [ 'packlink' ],
	AU: [ 'shipstation' ],
	NZ: [ 'shipstation' ],
};

const EXTENSION_PLUGIN_SLUGS: Record< ExtensionId, string > = {
	'woocommerce-shipping': 'woocommerce-shipping',
	shipstation: 'woocommerce-shipstation-integration',
	packlink: 'packlink-pro-shipping',
};

const ShippingRecommendations = () => {
	const [ pluginsBeingSetup, , handleInstall, handleActivate ] =
		useInstallPlugin();

	const {
		activePlugins,
		installedPlugins,
		countryCode,
		isSellingDigitalProductsOnly,
	} = useSelect( ( select ) => {
		const settings = select( settingsStore ).getSettings( 'general' );

		const { getActivePlugins, getInstalledPlugins } =
			select( pluginsStore );

		const profileItems =
			select( onboardingStore ).getProfileItems().product_types;

		return {
			activePlugins: getActivePlugins(),
			installedPlugins: getInstalledPlugins(),
			countryCode: getCountryCode(
				settings.general?.woocommerce_default_country
			),
			isSellingDigitalProductsOnly:
				profileItems?.length === 1 && profileItems[ 0 ] === 'downloads',
		};
	}, [] );

	if ( isSellingDigitalProductsOnly ) {
		return <ShippingTour showShippingRecommendationsStep={ false } />;
	}

	const extensionsForCountry =
		COUNTRY_EXTENSIONS_MAP[ countryCode ?? '' ] ?? [];

	const visibleExtensions = extensionsForCountry.filter(
		( ext ) => ! activePlugins.includes( EXTENSION_PLUGIN_SLUGS[ ext ] )
	);

	if ( visibleExtensions.length === 0 ) {
		return <ShippingTour showShippingRecommendationsStep={ false } />;
	}

	return (
		<div style={ { paddingBottom: 60 } }>
			<ShippingTour showShippingRecommendationsStep={ true } />
			<ShippingRecommendationsList>
				{ visibleExtensions.map( ( ext ) => {
					const isPluginInstalled = installedPlugins.includes(
						EXTENSION_PLUGIN_SLUGS[ ext ]
					);
					switch ( ext ) {
						case 'woocommerce-shipping':
							return (
								<WooCommerceShippingItem
									key={ ext }
									isPluginInstalled={ isPluginInstalled }
									pluginsBeingSetup={ pluginsBeingSetup }
									onInstallClick={ handleInstall }
									onActivateClick={ handleActivate }
								/>
							);
						case 'shipstation':
							return (
								<ShipStationItem
									key={ ext }
									isPluginInstalled={ isPluginInstalled }
									pluginsBeingSetup={ pluginsBeingSetup }
									onInstallClick={ handleInstall }
									onActivateClick={ handleActivate }
								/>
							);
						case 'packlink':
							return (
								<PacklinkItem
									key={ ext }
									isPluginInstalled={ isPluginInstalled }
									pluginsBeingSetup={ pluginsBeingSetup }
									onInstallClick={ handleInstall }
									onActivateClick={ handleActivate }
								/>
							);
						default:
							return null;
					}
				} ) }
			</ShippingRecommendationsList>
		</div>
	);
};

export default ShippingRecommendations;
