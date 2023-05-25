/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import WCSImage from '../images/wcs.svg';
import PrinterImage from '../images/printer.svg';
import PaperImage from '../images/paper.svg';
import DiscountImage from '../images/discount.svg';
import { PluginBanner } from './plugin-banner';

const features = [
	{
		icon: PrinterImage,
		title: __( 'Buy postage when you need it', 'woocommerce' ),
		description: __(
			'No need to wonder where that stampbook went.',
			'woocommerce'
		),
	},
	{
		icon: PaperImage,
		title: __( 'Print at home', 'woocommerce' ),
		description: __(
			'Pick up an order, then just pay, print, package and post.',
			'woocommerce'
		),
	},
	{
		icon: DiscountImage,
		title: __( 'Discounted rates', 'woocommerce' ),
		description: __(
			'Access discounted shipping rates with DHL and USPS.',
			'woocommerce'
		),
	},
];

export const WCSBanner = () => {
	return <PluginBanner logo={ { image: WCSImage } } features={ features } />;
};
