/**
 * External dependencies
 */
import { WC_BLOCKS_ASSET_URL } from '@woocommerce/block-settings';

/**
 * Array of common assets.
 */
export const commonIcons = [
	{
		id: 'alipay',
		alt: 'Alipay',
		src: WC_BLOCKS_ASSET_URL + 'img/payment-methods/alipay.svg',
	},
	{
		id: 'amex',
		alt: 'American Express',
		src: WC_BLOCKS_ASSET_URL + 'img/payment-methods/amex.svg',
	},
	{
		id: 'bancontact',
		alt: 'Bancontact',
		src: WC_BLOCKS_ASSET_URL + 'img/payment-methods/bancontact.svg',
	},
	{
		id: 'diners',
		alt: 'Diners Club',
		src: WC_BLOCKS_ASSET_URL + 'img/payment-methods/diners.svg',
	},
	{
		id: 'discover',
		alt: 'Discover',
		src: WC_BLOCKS_ASSET_URL + 'img/payment-methods/discover.svg',
	},
	{
		id: 'eps',
		alt: 'EPS',
		src: WC_BLOCKS_ASSET_URL + 'img/payment-methods/eps.svg',
	},
	{
		id: 'giropay',
		alt: 'Giropay',
		src: WC_BLOCKS_ASSET_URL + 'img/payment-methods/giropay.svg',
	},
	{
		id: 'ideal',
		alt: 'iDeal',
		src: WC_BLOCKS_ASSET_URL + 'img/payment-methods/ideal.svg',
	},
	{
		id: 'jcb',
		alt: 'JCB',
		src: WC_BLOCKS_ASSET_URL + 'img/payment-methods/jcb.svg',
	},
	{
		id: 'laser',
		alt: 'Laser',
		src: WC_BLOCKS_ASSET_URL + 'img/payment-methods/laser.svg',
	},
	{
		id: 'maestro',
		alt: 'Maestro',
		src: WC_BLOCKS_ASSET_URL + 'img/payment-methods/maestro.svg',
	},
	{
		id: 'mastercard',
		alt: 'Mastercard',
		src: WC_BLOCKS_ASSET_URL + 'img/payment-methods/mastercard.svg',
	},
	{
		id: 'multibanco',
		alt: 'Multibanco',
		src: WC_BLOCKS_ASSET_URL + 'img/payment-methods/multibanco.svg',
	},
	{
		id: 'p24',
		alt: 'Przelewy24',
		src: WC_BLOCKS_ASSET_URL + 'img/payment-methods/p24.svg',
	},
	{
		id: 'sepa',
		alt: 'Sepa',
		src: WC_BLOCKS_ASSET_URL + 'img/payment-methods/sepa.svg',
	},
	{
		id: 'sofort',
		alt: 'Sofort',
		src: WC_BLOCKS_ASSET_URL + 'img/payment-methods/sofort.svg',
	},
	{
		id: 'unionpay',
		alt: 'Union Pay',
		src: WC_BLOCKS_ASSET_URL + 'img/payment-methods/unionpay.svg',
	},
	{
		id: 'visa',
		alt: 'Visa',
		src: WC_BLOCKS_ASSET_URL + 'img/payment-methods/visa.svg',
	},
	{
		id: 'wechat',
		alt: 'WeChat',
		src: WC_BLOCKS_ASSET_URL + 'img/payment-methods/wechat.svg',
	},
];

/**
 * For a given ID, see if a common icon exists and return it's props.
 *
 * @param {string} id Icon ID.
 */
export const getCommonIconProps = ( id ) => {
	return (
		commonIcons.find( ( icon ) => {
			return icon.id === id;
		} ) || {}
	);
};
