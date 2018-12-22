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
import { IconNewReleases, IconWidgets } from './components/icons';
import ProductBestSellersBlock from './product-best-sellers';
import ProductByCategoryBlock from './product-category-block';
import ProductTopRatedBlock from './product-top-rated';
import ProductOnSaleBlock from './product-on-sale';
import ProductNewestBlock from './product-new';
import ProductsBlock from './handpicked-products';
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
	category: 'woocommerce',
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
	category: 'woocommerce',
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

registerBlockType( 'woocommerce/product-top-rated', {
	title: __( 'Top Rated Products', 'woo-gutenberg-products-block' ),
	icon: <Gridicon icon="trophy" />,
	category: 'woocommerce',
	keywords: [ __( 'WooCommerce', 'woo-gutenberg-products-block' ) ],
	description: __(
		'Display a grid of your top rated products.',
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
		return <ProductTopRatedBlock { ...props } />;
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
				{ getShortcode( props, 'woocommerce/product-top-rated' ) }
			</RawHTML>
		);
	},
} );

registerBlockType( 'woocommerce/product-on-sale', {
	title: __( 'On Sale Products', 'woo-gutenberg-products-block' ),
	icon: <Gridicon icon="tag" />,
	category: 'woocommerce',
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

registerBlockType( 'woocommerce/product-new', {
	title: __( 'Newest Products', 'woo-gutenberg-products-block' ),
	icon: <IconNewReleases />,
	category: 'woocommerce',
	keywords: [ __( 'WooCommerce', 'woo-gutenberg-products-block' ) ],
	description: __(
		'Display a grid of your newest products.',
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
		return <ProductNewestBlock { ...props } />;
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
				{ getShortcode( props, 'woocommerce/product-new' ) }
			</RawHTML>
		);
	},
} );

/**
 * Register and run the "Hand-picked Products" block.
 */
registerBlockType( 'woocommerce/handpicked-products', {
	title: __( 'Hand-picked Products', 'woo-gutenberg-products-block' ),
	icon: <IconWidgets />,
	category: 'woocommerce',
	keywords: [ __( 'WooCommerce', 'woo-gutenberg-products-block' ) ],
	description: __(
		'Display a selection of hand-picked products in a grid.',
		'woo-gutenberg-products-block'
	),
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
	},
	getEditWrapperProps,

	/**
	 * Renders and manages the block.
	 */
	edit( props ) {
		return <ProductsBlock { ...props } />;
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
				{ getShortcode( props, 'woocommerce/handpicked-products' ) }
			</RawHTML>
		);
	},
} );
