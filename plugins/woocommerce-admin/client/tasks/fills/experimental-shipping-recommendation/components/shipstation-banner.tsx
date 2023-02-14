/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import ShipStationImage from '../images/shipstation.svg';
import TimerImage from '../images/timer.svg';
import StarImage from '../images/star.svg';
import DiscountImage from '../images/discount.svg';
import { PluginBanner } from './plugin-banner';

const features = [
	{
		icon: TimerImage,
		title: __( 'Save your time', 'woocommerce' ),
		description: __(
			'Import your orders automatically, no matter where you sell.',
			'woocommerce'
		),
	},
	{
		icon: DiscountImage,
		title: __( 'Save your money', 'woocommerce' ),
		description: __(
			'Live shipping rates amongst all the carrier choices.',
			'woocommerce'
		),
	},
	{
		icon: StarImage,
		title: __( 'Wow your shoppers', 'woocommerce' ),
		description: __(
			'Customize notification emails, packing slips, shipping labels.',
			'woocommerce'
		),
	},
];
export const ShipStationBanner = () => {
	return (
		<PluginBanner
			logo={ { image: ShipStationImage } }
			features={ features }
		/>
	);
};
