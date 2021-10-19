/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { getSetting } from '@woocommerce/settings';
import { Icon, more } from '@woocommerce/icons';

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
		src: <Icon srcElement={ more } />,
		foreground: '#96588a',
	},
	category: 'woocommerce',
	keywords: [ __( 'WooCommerce', 'woocommerce' ) ],
	description: __(
		'Display a grid of products with selected tags.',
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
		 * Number of columns.
		 */
		columns: {
			type: 'number',
			default: getSetting( 'default_columns', 3 ),
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
