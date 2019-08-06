/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import './editor.scss';
import Block from './block';
import { deprecatedConvertToShortcode } from '../../utils/deprecations';
import { IconWidgets } from '../../components/icons';

registerBlockType( 'woocommerce/handpicked-products', {
	title: __( 'Hand-picked Products', 'woo-gutenberg-products-block' ),
	icon: {
		src: <IconWidgets />,
		foreground: '#96588a',
	},
	category: 'woocommerce',
	keywords: [ __( 'WooCommerce', 'woo-gutenberg-products-block' ) ],
	description: __(
		'Display a selection of hand-picked products in a grid.',
		'woo-gutenberg-products-block'
	),
	supports: {
		align: [ 'wide', 'full' ],
		html: false,
	},
	attributes: {
		/**
		 * Alignment of product grid
		 */
		align: {
			type: 'string',
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
		 * The list of product IDs to display
		 */
		products: {
			type: 'array',
			default: [],
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
				align: {
					type: 'string',
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
				products: {
					type: 'array',
					default: [],
				},
			},
			save: deprecatedConvertToShortcode( 'woocommerce/handpicked-products' ),
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
