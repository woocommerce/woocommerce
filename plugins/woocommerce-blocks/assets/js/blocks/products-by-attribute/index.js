/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import Gridicon from 'gridicons';
import { RawHTML } from '@wordpress/element';
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import './style.scss';
import Block from './block';
import getShortcode from '../../utils/get-shortcode';

registerBlockType( 'woocommerce/products-by-attribute', {
	title: __( 'Products by Attribute', 'woo-gutenberg-products-block' ),
	icon: <Gridicon icon="custom-post-type" />,
	category: 'woocommerce',
	keywords: [ __( 'WooCommerce', 'woo-gutenberg-products-block' ) ],
	description: __(
		'Display a grid of products from your selected attributes.',
		'woo-gutenberg-products-block'
	),
	supports: {
		align: [ 'wide', 'full' ],
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
	},

	/**
	 * Renders and manages the block.
	 */
	edit( props ) {
		return <Block { ...props } />;
	},

	/**
	 * Save the block content in the post content. Block content is saved as a products shortcode.
	 *
	 * @return string
	 */
	save( props ) {
		const {
			align,
		} = props.attributes; /* eslint-disable-line react/prop-types */
		return (
			<RawHTML className={ align ? `align${ align }` : '' }>
				{ getShortcode( props, 'woocommerce/products-by-attribute' ) }
			</RawHTML>
		);
	},
} );
