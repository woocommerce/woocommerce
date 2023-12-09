/**
 * External dependencies
 */
import { getCategories, setCategories } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { woo } from '@woocommerce/icons';
import { Icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import '../css/editor.scss';
import '../css/style.scss';
import './filters/block-list-block';
import './filters/get-block-attributes';
import './base/components/notice-banner/style.scss';
import './atomic/utils/blocks-registration-manager';

setCategories( [
	...getCategories().filter(
		( { slug } ) =>
			slug !== 'woocommerce' && slug !== 'woocommerce-product-elements'
	),
	{
		slug: 'woocommerce',
		title: __( 'WooCommerce', 'woo-gutenberg-products-block' ),
		icon: <Icon icon={ woo } />,
	},
	{
		slug: 'woocommerce-product-elements',
		title: __(
			'WooCommerce Product Elements',
			'woo-gutenberg-products-block'
		),
		icon: (
			<Icon
				icon={ woo }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
] );
