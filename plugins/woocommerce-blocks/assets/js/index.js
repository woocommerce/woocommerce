/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import Gridicon from 'gridicons';
import { registerBlockType } from '@wordpress/blocks';
import { RawHTML } from '@wordpress/element';

/**
 * Internal dependencies
 */
import '../css/product-category-block.scss';
import getShortcode from './utils/get-shortcode';
import ProductBestSellersBlock from './product-best-sellers';
import ProductByCategoryBlock from './product-category-block';
import ProductOnSaleBlock from './product-on-sale';
import sharedAttributes from './utils/shared-attributes';

const validAlignments = [ 'wide', 'full' ];

const getEditWrapperProps = ( attributes ) => {
	const { align } = attributes;
	if ( -1 !== validAlignments.indexOf( align ) ) {
		return { 'data-align': align };
	}
};

/**
 * Register and run the "Products by Category" block.
 */
registerBlockType( 'woocommerce/product-category', {
	title: __( 'Products by Category', 'woo-gutenberg-products-block' ),
	icon: 'category',
	category: 'widgets',
	keywords: [ __( 'WooCommerce', 'woo-gutenberg-products-block' ) ],
	description: __(
		'Display a grid of products from your selected categories.',
		'woo-gutenberg-products-block'
	),
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
	getEditWrapperProps,

	/**
	 * Renders and manages the block.
	 */
	edit( props ) {
		return <ProductByCategoryBlock { ...props } />;
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

registerBlockType( 'woocommerce/product-best-sellers', {
	title: __( 'Best Selling Products', 'woo-gutenberg-products-block' ),
	icon: <Gridicon icon="stats-up-alt" />,
	category: 'widgets',
	keywords: [ __( 'WooCommerce', 'woo-gutenberg-products-block' ) ],
	description: __(
		'Display a grid of your all-time best selling products.',
		'woo-gutenberg-products-block'
	),
	attributes: {
		...sharedAttributes,
	},
	getEditWrapperProps,

	/**
	 * Renders and manages the block.
	 */
	edit( props ) {
		return <ProductBestSellersBlock { ...props } />;
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
				{ getShortcode( props, 'woocommerce/product-best-sellers' ) }
			</RawHTML>
		);
	},
} );

registerBlockType( 'woocommerce/product-on-sale', {
	title: __( 'On Sale Products', 'woo-gutenberg-products-block' ),
	icon: <Gridicon icon="tag" />,
	category: 'widgets',
	keywords: [ __( 'WooCommerce', 'woo-gutenberg-products-block' ) ],
	description: __(
		'Display a grid of on sale products.',
		'woo-gutenberg-products-block'
	),
	attributes: {
		...sharedAttributes,
		/**
		 * How to order the products: 'date', 'popularity', 'price_asc', 'price_desc' 'rating', 'title'.
		 */
		orderby: {
			type: 'string',
			default: 'date',
		},
	},
	getEditWrapperProps,

	/**
	 * Renders and manages the block.
	 */
	edit( props ) {
		return <ProductOnSaleBlock { ...props } />;
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
				{ getShortcode( props, 'woocommerce/product-on-sale' ) }
			</RawHTML>
		);
	},
} );
