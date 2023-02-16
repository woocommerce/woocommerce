/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { WCSBanner } from '../../experimental-shipping-recommendation/components/wcs-banner';
import { SinglePartnerFeatures } from '../single-partner-features';
import { PluginBanner } from '../../experimental-shipping-recommendation/components/plugin-banner';
import ShipStationImage from './assets/shipstation.svg';
import SendcloudImage from './assets/sendcloud-banner-side.svg';
import PacklinkImage from './assets/packlink-banner-side.svg';
import EnviaImage from './assets/envia-banner-side.svg';
import SkydropxImage from './assets/skydropx-banner-side.svg';
import CheckIcon from './assets/check.svg';
import SendCloudColumnImage from './assets/sendcloud-column-logo.svg';
import PacklinkColumnImage from './assets/packlink-column-logo.svg';
import ShipStationColumnImage from './assets/shipstation-column-logo.svg';
/**
 * Banner layout for Envia
 */
export const EnviaSinglePartner = () => {
	return (
		<PluginBanner
			features={ SinglePartnerFeatures }
			logo={ { image: EnviaImage } }
		/>
	);
};

/**
 * Banner layout for Envia
 */
export const SkydropxSinglePartner = () => {
	return (
		<PluginBanner
			features={ SinglePartnerFeatures }
			logo={ { image: SkydropxImage } }
		/>
	);
};

/**
 * Banner layout for Sendcloud
 */
export const SendcloudSinglePartner = () => {
	return (
		<PluginBanner
			features={ SinglePartnerFeatures }
			logo={ { image: SendcloudImage } }
		/>
	);
};

/**
 * Narrow Column Layout for Sendcloud
 */
export const SendcloudDualPartner = () => {
	const features = [
		{
			icon: CheckIcon,
			description: __( 'Print labels from 80+ carriers', 'woocommerce' ),
		},
		{
			icon: CheckIcon,
			description: __(
				'Process orders in just a few clicks',
				'woocommerce'
			),
		},
		{
			icon: CheckIcon,
			description: __( 'Customize checkout options', 'woocommerce' ),
		},
		{
			icon: CheckIcon,
			description: __( 'Self-service tracking & returns', 'woocommerce' ),
		},
		{
			icon: CheckIcon,
			description: __( 'Start with a free plan', 'woocommerce' ),
		},
	];
	return (
		<PluginBanner
			layout="dual"
			features={ features }
			logo={ { image: SendCloudColumnImage } }
		>
			<div>test</div>
		</PluginBanner>
	);
};

/**
 * Banner layout for Packlink
 */
export const PacklinkSinglePartner = () => {
	return (
		<PluginBanner
			features={ SinglePartnerFeatures }
			logo={ { image: PacklinkImage } }
		/>
	);
};

/**
 * Narrow Column Layout for Packlink
 */
export const PacklinkDualPartner = () => {
	const features = [
		{
			icon: CheckIcon,
			description: __(
				'Automated, real-time order import',
				'woocommerce'
			),
		},
		{
			icon: CheckIcon,
			description: __(
				'Direct access to leading carriers',
				'woocommerce'
			),
		},
		{
			icon: CheckIcon,
			description: __(
				'Access competitive shipping prices',
				'woocommerce'
			),
		},
		{
			icon: CheckIcon,
			description: __( 'Quickly bulk print labels', 'woocommerce' ),
		},
		{
			icon: CheckIcon,
			description: __( 'Free shipping platform', 'woocommerce' ),
		},
	];
	return (
		<PluginBanner
			layout="dual"
			features={ features }
			logo={ { image: PacklinkColumnImage } }
		/>
	);
};

/**
 * Banner layout for ShipStation
 */
export const ShipStationSinglePartner = () => {
	return (
		<PluginBanner
			features={ SinglePartnerFeatures }
			logo={ { image: ShipStationImage } }
		/>
	);
};

/**
 * Narrow Column Layout for ShipStation
 */
export const ShipStationDualPartner = () => {
	const features = [
		{
			icon: CheckIcon,
			description: __(
				'Print labels from Royal Mail, Parcel Force, DPD, and many more',
				'woocommerce'
			),
		},
		{
			icon: CheckIcon,
			description: __(
				'Shop for the best rates, in real-time',
				'woocommerce'
			),
		},
		{
			icon: CheckIcon,
			description: __( 'Connect selling channels easily', 'woocommerce' ),
		},
		{
			icon: CheckIcon,
			description: __( 'Advance automated workflows', 'woocommerce' ),
		},
		{
			icon: CheckIcon,
			description: __( '30-days free trial', 'woocommerce' ),
		},
	];
	return (
		<PluginBanner
			layout="dual"
			features={ features }
			logo={ { image: ShipStationColumnImage } }
		/>
	);
};

/**
 * Banner layout for WooCommerce Shipping
 * TODO: might not need to implement: see slack
 */
export const WooCommerceShippingSinglePartner = () => {
	return <WCSBanner />;
};
