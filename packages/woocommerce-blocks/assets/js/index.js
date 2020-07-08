/**
 * External dependencies
 */
import { getCategories, setCategories } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { woo as Icon } from '@woocommerce/icons';

/**
 * Internal dependencies
 */
import '../css/editor.scss';
import '../css/style.scss';
import './filters/block-list-block';
import './filters/get-block-attributes';

setCategories( [
	...getCategories().filter( ( { slug } ) => slug !== 'woocommerce' ),
	// Add a WooCommerce block category
	{
		slug: 'woocommerce',
		title: __( 'WooCommerce', 'woocommerce' ),
		icon: <Icon />,
	},
] );
