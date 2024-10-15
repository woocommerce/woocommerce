/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

import {
	PLUGINS_STORE_NAME,
	SETTINGS_STORE_NAME,
	ONBOARDING_STORE_NAME,
} from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { getCountryCode } from '~/dashboard/utils';
import WooCommerceServicesItem from './experimental-woocommerce-services-item';
import { ShippingRecommendationsList } from './shipping-recommendations';
import './shipping-recommendations.scss';
import { ShippingTour } from '../guided-tours/shipping-tour';

const ShippingRecommendations: React.FC = () => {
	const {
		activePlugins,
		installedPlugins,
		countryCode,
		isJetpackConnected,
		isSellingDigitalProductsOnly,
	} = useSelect( ( select ) => {
		const settings = select( SETTINGS_STORE_NAME ).getSettings( 'general' );

		const {
			getActivePlugins,
			getInstalledPlugins,
			isJetpackConnected: _isJetpackConnected,
		} = select( PLUGINS_STORE_NAME );

		const profileItems = select( ONBOARDING_STORE_NAME ).getProfileItems()
			.product_types;

		return {
			activePlugins: getActivePlugins(),
			installedPlugins: getInstalledPlugins(),
			countryCode: getCountryCode(
				settings.general?.woocommerce_default_country
			),
			isJetpackConnected: _isJetpackConnected(),
			isSellingDigitalProductsOnly:
				profileItems?.length === 1 && profileItems[ 0 ] === 'downloads',
		};
	} );

	if (
		activePlugins.includes( 'woocommerce-shipping' ) ||
		activePlugins.includes( 'woocommerce-tax' )
	) {
		return <ShippingTour showShippingRecommendationsStep={ false } />;
	}

	if (
		activePlugins.includes( 'woocommerce-services' ) &&
		isJetpackConnected
	) {
		return <ShippingTour showShippingRecommendationsStep={ false } />;
	}

	if ( countryCode !== 'US' || isSellingDigitalProductsOnly ) {
		return <ShippingTour showShippingRecommendationsStep={ false } />;
	}

	return (
		<>
			<ShippingTour showShippingRecommendationsStep={ true } />
			<ShippingRecommendationsList>
				<WooCommerceServicesItem
					isWCSInstalled={ installedPlugins.includes(
						'woocommerce-services'
					) }
				/>
			</ShippingRecommendationsList>
		</>
	);
};

export default ShippingRecommendations;
