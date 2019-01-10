/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { RawHTML } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './style.scss';
import Block from './block';
import getShortcode from '../../utils/get-shortcode';
import sharedAttributes from '../../utils/shared-attributes';

/**
 * Register and run the "Products by Category" block.
 */
registerBlockType( 'woocommerce/product-category', {
	title: __( 'Products by Category', 'woo-gutenberg-products-block' ),
	icon: 'category',
	category: 'woocommerce',
	keywords: [ __( 'WooCommerce', 'woo-gutenberg-products-block' ) ],
	description: __(
		'Display a grid of products from your selected categories.',
		'woo-gutenberg-products-block'
	),
	supports: {
		align: [ 'wide', 'full' ],
	},
	attributes: {
		...sharedAttributes,

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
				{ getShortcode( props, 'woocommerce/product-category' ) }
			</RawHTML>
		);
	},
} );
