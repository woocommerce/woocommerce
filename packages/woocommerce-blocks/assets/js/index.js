/**
 * External dependencies
 */
import { getCategories, setCategories } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import '../css/editor.scss';
import '../css/style.scss';
import { IconWoo } from './components/icons';

setCategories( [
	...getCategories().filter( ( { slug } ) => slug !== 'woocommerce' ),
	// Add a WooCommerce block category
	{
		slug: 'woocommerce',
		title: __( 'WooCommerce', 'woocommerce' ),
		icon: <IconWoo />,
	},
] );
