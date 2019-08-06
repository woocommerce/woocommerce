/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import Gridicon from 'gridicons';
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import './editor.scss';
import Block from './block';
import { deprecatedConvertToShortcode } from '../../utils/deprecations';

const blockTypeName = 'woocommerce/products-by-attribute';

registerBlockType( blockTypeName, {
	title: __( 'Products by Attribute', 'woo-gutenberg-products-block' ),
	icon: {
		src: <Gridicon icon="custom-post-type" />,
		foreground: '#96588a',
	},
	category: 'woocommerce',
	keywords: [ __( 'WooCommerce', 'woo-gutenberg-products-block' ) ],
	description: __(
		'Display a grid of products from your selected attributes.',
		'woo-gutenberg-products-block'
	),
	supports: {
		align: [ 'wide', 'full' ],
		html: false,
	},
	attributes: {
		/**
		 * Product attributes, used to display only products with the given attributes.
		 */
		attributes: {
			type: 'array',
			default: [],
		},

		/**
		 * Product attribute operator, used to restrict to products in all or any selected attributes.
		 */
		attrOperator: {
			type: 'string',
			default: 'any',
		},

		/**
		 * Number of columns.
		 */
		columns: {
			type: 'number',
			default: wc_product_block_data.default_columns,
		},

		/**
		 * Toggle for edit mode in the block preview.
		 */
		editMode: {
			type: 'boolean',
			default: true,
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
		 * How to order the products: 'date', 'popularity', 'price_asc', 'price_desc' 'rating', 'title'.
		 */
		orderby: {
			type: 'string',
			default: 'date',
		},

		/**
		 * Number of rows.
		 */
		rows: {
			type: 'number',
			default: wc_product_block_data.default_rows,
		},

		/**
		 * How to align cart buttons.
		 */
		alignButtons: {
			type: 'boolean',
			default: false,
		},
	},

	deprecated: [
		{
			// Deprecate shortcode save method in favor of dynamic rendering.
			attributes: {
				attributes: {
					type: 'array',
					default: [],
				},
				attrOperator: {
					type: 'string',
					default: 'any',
				},
				columns: {
					type: 'number',
					default: wc_product_block_data.default_columns,
				},
				editMode: {
					type: 'boolean',
					default: true,
				},
				contentVisibility: {
					type: 'object',
					default: {
						title: true,
						price: true,
						rating: true,
						button: true,
					},
				},
				orderby: {
					type: 'string',
					default: 'date',
				},
				rows: {
					type: 'number',
					default: wc_product_block_data.default_rows,
				},
			},
			save: deprecatedConvertToShortcode( blockTypeName ),
		},
	],

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
