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
import { ShippingRecommendationsList } from './shipping-recommendations';
import './shipping-recommendations.scss';
import { ShippingTour } from '../guided-tours/shipping-tour';

const ShippingRecommendations: React.FC = () => {
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

	if ( activePlugins.includes( 'woocommerce-shipping' ) ) {
		return <ShippingTour showShippingRecommendationsStep={ false } />;
	}

	if ( countryCode !== 'US' || isSellingDigitalProductsOnly ) {
		return <ShippingTour showShippingRecommendationsStep={ false } />;
	}

	return (
		<>
			<ShippingTour showShippingRecommendationsStep={ true } />
			<ShippingRecommendationsList>
				<WooCommerceShippingItem
					isPluginInstalled={ installedPlugins.includes(
						'woocommerce-shipping'
					) }
				/>
			</ShippingRecommendationsList>
		</>
	);
};

export default ShippingRecommendations;
