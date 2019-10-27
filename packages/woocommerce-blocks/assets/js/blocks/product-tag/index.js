/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { DEFAULT_COLUMNS, DEFAULT_ROWS } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import './editor.scss';
import Block from './block';

/**
 * Register and run the "Products by Tag" block.
 */
registerBlockType( 'woocommerce/product-tag', {
	title: __( 'Products by Tag', 'woocommerce' ),
	icon: {
		src: 'tag',
		foreground: '#96588a',
	},
	category: 'woocommerce',
	keywords: [ __( 'WooCommerce', 'woocommerce' ) ],
	description: __(
		'Display a grid of products from your selected tags.',
		'woocommerce'
	),
	supports: {
		align: [ 'wide', 'full' ],
		html: false,
	},
	attributes: {
		/**
		 * Number of columns.
		 */
		columns: {
			type: 'number',
			default: DEFAULT_COLUMNS,
		},

		/**
		 * Number of rows.
		 */
		rows: {
			type: 'number',
			default: DEFAULT_ROWS,
		},

		/**
		 * How to align cart buttons.
		 */
		alignButtons: {
			type: 'boolean',
			default: false,
		},

		/**
		 * Content visibility setting
		 */
		contentVisibility: {
			type: 'object',
			default: {
				title: true,
				price: true,
				rating: true,
				button: true,
			},
		},

		/**
		 * Product tags, used to display only products with the given tags.
		 */
		tags: {
			type: 'array',
			default: [],
		},

		/**
		 * Product tags operator, used to restrict to products in all or any selected tags.
		 */
		tagOperator: {
			type: 'string',
			default: 'any',
		},

		/**
		 * How to order the products: 'date', 'popularity', 'price_asc', 'price_desc' 'rating', 'title'.
		 */
		orderby: {
			type: 'string',
			default: 'date',
		},
	},

	/**
	 * Renders and manages the block.
	 */
	edit( props ) {
		return <Block { ...props } />;
	},

	save() {
		return null;
	},
} );
