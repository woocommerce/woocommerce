/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { Component, Fragment, RawHTML } from '@wordpress/element';
import {
	BlockAlignmentToolbar,
	BlockControls,
	InspectorControls,
} from '@wordpress/editor';
import {
	PanelBody,
	Placeholder,
	RangeControl,
	SelectControl,
	Spinner,
	TreeSelect,
} from '@wordpress/components';
import PropTypes from 'prop-types';
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import '../css/product-category-block.scss';
import getQuery from './utils/get-query';
import getShortcode from './utils/get-shortcode';
import ProductPreview from './components/product-preview';
import sharedAttributes from './utils/shared-attributes';

// Only enable center, wide, and full alignments
const validAlignments = [ 'center', 'wide', 'full' ];

/**
 * Component to handle edit mode of "Products by Category".
 */
class ProductByCategoryBlock extends Component {
	constructor() {
		super( ...arguments );
		this.state = {
			categoriesList: [],
			products: [],
			loaded: false,
		};
	}

	componentDidMount() {
		apiFetch( {
			path: addQueryArgs( '/wc/v3/products/categories', { per_page: -1 } ),
		} )
			.then( ( categoriesList ) => {
				this.setState( { categoriesList } );
			} )
			.catch( () => {
				this.setState( { categoriesList: [] } );
			} );
		if ( this.props.attributes.categories ) {
			this.getProducts();
		}
	}

	componentDidUpdate( prevProps ) {
		const hasChange = [ 'rows', 'columns', 'orderby', 'categories' ].reduce(
			( acc, key ) => {
				return acc || prevProps.attributes[ key ] !== this.props.attributes[ key ];
			},
			false
		);
		if ( hasChange ) {
			this.getProducts();
		}
	}

	getProducts() {
		this.setState( { products: [], loaded: false } );
		apiFetch( {
			path: addQueryArgs( '/wgbp/v3/products', getQuery( this.props.attributes ) ),
		} )
			.then( ( products ) => {
				this.setState( { products, loaded: true } );
			} )
			.catch( () => {
				this.setState( { products: [], loaded: true } );
			} );
	}

	getInspectorControls() {
		const { attributes, setAttributes } = this.props;
		const { columns, orderby, rows, categories } = attributes;
		const { categoriesList } = this.state;

		return (
			<InspectorControls key="inspector">
				<PanelBody title={ __( 'Product Category', 'woocommerce' ) } initialOpen>
					<TreeSelect
						label={ __( 'Product Category', 'woocommerce' ) }
						tree={ categoriesList }
						selectedId={ categories }
						multiple
						onChange={ ( value ) => {
							setAttributes( { categories: value ? value : [] } );
						} }
					/>
				</PanelBody>
				<PanelBody title={ __( 'Layout', 'woocommerce' ) } initialOpen={ false }>
					<RangeControl
						label={ __( 'Columns', 'woocommerce' ) }
						value={ columns }
						onChange={ ( value ) => setAttributes( { columns: value } ) }
						min={ wc_product_block_data.min_columns }
						max={ wc_product_block_data.max_columns }
					/>
					<RangeControl
						label={ __( 'Rows', 'woocommerce' ) }
						value={ rows }
						onChange={ ( value ) => setAttributes( { rows: value } ) }
						min={ wc_product_block_data.min_rows }
						max={ wc_product_block_data.max_rows }
					/>
				</PanelBody>
				<PanelBody title={ __( 'Order By', 'woocommerce' ) } initialOpen={ false }>
					<SelectControl
						label={ __( 'Order products by', 'woocommerce' ) }
						value={ orderby }
						options={ [
							{
								label: __( 'Newness - newest first', 'woocommerce' ),
								value: 'date',
							},
							{
								label: __( 'Price - low to high', 'woocommerce' ),
								value: 'price_asc',
							},
							{
								label: __( 'Price - high to low', 'woocommerce' ),
								value: 'price_desc',
							},
							{
								label: __( 'Rating - highest first', 'woocommerce' ),
								value: 'rating',
							},
							{
								label: __( 'Sales - most first', 'woocommerce' ),
								value: 'popularity',
							},
							{
								label: __( 'Title - alphabetical', 'woocommerce' ),
								value: 'title',
							},
							{
								label: __( 'Menu Order', 'woocommerce' ),
								value: 'menu_order',
							},
						] }
						onChange={ ( value ) => setAttributes( { orderby: value } ) }
					/>
				</PanelBody>
			</InspectorControls>
		);
	}

	render() {
		const { setAttributes } = this.props;
		const { columns, align } = this.props.attributes;
		const { loaded, products } = this.state;

		return (
			<Fragment>
				<BlockControls>
					<BlockAlignmentToolbar
						controls={ validAlignments }
						value={ align }
						onChange={ ( nextAlign ) => setAttributes( { align: nextAlign } ) }
					/>
				</BlockControls>
				{ this.getInspectorControls() }
				<div className={ `wc-block-products-category cols-${ columns }` }>
					{ products.length ? (
						products.map( ( product ) => (
							<ProductPreview product={ product } key={ product.id } />
						) )
					) : (
						<Placeholder
							icon="category"
							label={ __( 'Products by Category', 'woocommerce' ) }
						>
							{ ! loaded ? (
								<Spinner />
							) : (
								__( 'No products in this category.', 'woocommerce' )
							) }
						</Placeholder>
					) }
				</div>
			</Fragment>
		);
	}
}

ProductByCategoryBlock.propTypes = {
	/**
	 * The attributes for this block
	 */
	attributes: PropTypes.object.isRequired,
	/**
	 * A callback to update attributes
	 */
	setAttributes: PropTypes.func.isRequired,
};

export default ProductByCategoryBlock;

/**
 * Register and run the "Products by Category" block.
 */
registerBlockType( 'woocommerce/product-category', {
	title: __( 'Products by Category', 'woocommerce' ),
	icon: 'category',
	category: 'widgets',
	description: __( 'Display a grid of products from your selected categories.', 'woocommerce' ),
	attributes: {
		...sharedAttributes,
		edit_mode: {
			type: 'boolean',
			default: true,
		},
		categories: {
			type: 'array',
			default: [],
		},
	},

	getEditWrapperProps( attributes ) {
		const { align } = attributes;
		if ( -1 !== validAlignments.indexOf( align ) ) {
			return { 'data-align': align };
		}
	},

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
		return <RawHTML className={ `align${ align }` }>{ getShortcode( props ) }</RawHTML>;
	},
} );
