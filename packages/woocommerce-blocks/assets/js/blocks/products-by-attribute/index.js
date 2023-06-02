/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon, tags } from '@woocommerce/icons';
import { registerBlockType } from '@wordpress/blocks';
import { DEFAULT_COLUMNS, DEFAULT_ROWS } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import './editor.scss';
import Block from './block';
import { deprecatedConvertToShortcode } from '../../utils/deprecations';

const blockTypeName = 'woocommerce/products-by-attribute';

registerBlockType( blockTypeName, {
	title: __( 'Products by Attribute', 'woocommerce' ),
	icon: {
		src: <Icon srcElement={ tags } />,
		foreground: '#96588a',
	},
	category: 'woocommerce',
	keywords: [ __( 'WooCommerce', 'woocommerce' ) ],
	description: __(
		'Display a grid of products with selected attributes.',
		'woocommerce'
	),
	supports: {
		align: [ 'wide', 'full' ],
		html: false,
	},
	example: {
		attributes: {
			isPreview: true,
		},
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
			default: DEFAULT_COLUMNS,
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
		 * Are we previewing?
		 */
		isPreview: {
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
					default: DEFAULT_COLUMNS,
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
					default: DEFAULT_ROWS,
				},
			},
			save: deprecatedConvertToShortcode( blockTypeName ),
		},
	],

	/**
	 * Renders and manages the block.
	 *
	 * @param {Object} props Props to pass to block.
	 */
	edit( props ) {
		return <Block { ...props } />;
	},

	save() {
		return null;
	},
} );
