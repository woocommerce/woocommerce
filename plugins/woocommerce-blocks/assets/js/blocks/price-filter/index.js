/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import edit from './edit.js';
import { IconMoney } from '../../components/icons';

registerBlockType( 'woocommerce/price-filter', {
	title: __( 'Filter Products by Price', 'woo-gutenberg-products-block' ),
	icon: {
		src: <IconMoney />,
		foreground: '#96588a',
	},
	category: 'woocommerce',
	keywords: [ __( 'WooCommerce', 'woo-gutenberg-products-block' ) ],
	description: __(
		'Display a slider to filter products in your store by price.',
		'woo-gutenberg-products-block'
	),
	supports: {
		align: [ 'wide', 'full' ],
	},

	attributes: {
		showInputFields: {
			type: 'boolean',
			default: true,
		},
		showFilterButton: {
			type: 'boolean',
			default: false,
		},
	},

	edit,

	/**
	 * Save the props to post content.
	 */
	save( { attributes } ) {
		const { showInputFields, showFilterButton } = attributes;
		const data = {
			'data-showinputfields': showInputFields,
			'data-showfilterbutton': showFilterButton,
		};
		return (
			<div className="is-loading" { ...data }>
				<span aria-hidden className="wc-block-product-categories__placeholder" />
			</div>
		);
	},
} );
