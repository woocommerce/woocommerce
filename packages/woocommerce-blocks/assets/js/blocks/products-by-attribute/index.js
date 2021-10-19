/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon, tags } from '@woocommerce/icons';
import { registerBlockType } from '@wordpress/blocks';
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import './editor.scss';
import Block from './block';

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
			default: getSetting( 'default_columns', 3 ),
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
			default: getSetting( 'default_rows', 3 ),
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
