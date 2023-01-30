/**
 * External dependencies
 */
import { getCategories, setCategories } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { woo, atom, Icon } from '@woocommerce/icons';

/**
 * Internal dependencies
 */
import '../css/editor.scss';
import '../css/style.scss';
import './filters/block-list-block';
import './filters/get-block-attributes';

setCategories( [
	...getCategories().filter(
		( { slug } ) =>
			slug !== 'woocommerce' && slug !== 'woocommerce-product-elements'
	),
	{
		slug: 'woocommerce',
		title: __( 'WooCommerce', 'woo-gutenberg-products-block' ),
		icon: <Icon srcElement={ woo } />,
	},
	{
		slug: 'woocommerce-product-elements',
		title: __(
			'WooCommerce Product Elements',
			'woo-gutenberg-products-block'
		),
		icon: <Icon srcElement={ atom } style={ { fill: '#874FB9' } } />,
	},
] );
