/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import {
	pluginsStore,
	settingsStore,
	onboardingStore,
} from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { createNoticesFromResponse } from '../lib/notices';
import { getCountryCode } from '~/dashboard/utils';
import WooCommerceShippingItem from './experimental-woocommerce-shipping-item';
import ShipStationItem from './shipstation-item';
import PacklinkItem from './packlink-item';
import { ShippingRecommendationsList } from './shipping-recommendations';
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
	const [ pluginsBeingSetup, setPluginsBeingSetup ] = useState<
		Array< string >
	>( [] );

	const { installAndActivatePlugins } = useDispatch( pluginsStore );

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

	const handleSetup = ( slugs: string[] ): PromiseLike< void > => {
		if ( pluginsBeingSetup.length > 0 ) {
			return Promise.resolve();
		}

		setPluginsBeingSetup( slugs );

		return installAndActivatePlugins( slugs )
			.then( () => {
				setPluginsBeingSetup( [] );
			} )
			.catch( ( response: { errors: Record< string, string > } ) => {
				createNoticesFromResponse( response );
				setPluginsBeingSetup( [] );

				return Promise.reject();
			} );
	};

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
		<>
			<ShippingTour showShippingRecommendationsStep={ true } />
			<ShippingRecommendationsList>
				{ visibleExtensions.map( ( ext ) => {
					switch ( ext ) {
						case 'woocommerce-shipping':
							return (
								<WooCommerceShippingItem
									key={ ext }
									isPluginInstalled={ installedPlugins.includes(
										'woocommerce-shipping'
									) }
								/>
							);
						case 'shipstation':
							return (
								<ShipStationItem
									key={ ext }
									pluginsBeingSetup={ pluginsBeingSetup }
									onSetupClick={ handleSetup }
								/>
							);
						case 'packlink':
							return (
								<PacklinkItem
									key={ ext }
									pluginsBeingSetup={ pluginsBeingSetup }
									onSetupClick={ handleSetup }
								/>
							);
						default:
							return null;
					}
				} ) }
			</ShippingRecommendationsList>
		</>
	);
};

export default ShippingRecommendations;
