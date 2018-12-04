/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import apiFetch from '@wordpress/api-fetch';
import { Component, Fragment, RawHTML } from '@wordpress/element';
import {
	BlockAlignmentToolbar,
	BlockControls,
	InspectorControls,
} from '@wordpress/editor';
import {
	Button,
	PanelBody,
	Placeholder,
	RangeControl,
	SelectControl,
	Spinner,
	Toolbar,
	withSpokenMessages,
} from '@wordpress/components';
import PropTypes from 'prop-types';
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import '../css/product-category-block.scss';
import getQuery from './utils/get-query';
import getShortcode from './utils/get-shortcode';
import ProductCategoryControl from './components/product-category-control';
import ProductPreview from './components/product-preview';
import sharedAttributes from './utils/shared-attributes';

// Only enable center, wide, and full alignments
const validAlignments = [ 'center', 'wide', 'full' ];

/**
 * Component to handle edit mode of "Products by Category".
 */
export default class ProductByCategoryBlock extends Component {
	constructor() {
		super( ...arguments );
		this.state = {
			products: [],
			loaded: false,
		};
	}

	componentDidMount() {
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
			path: addQueryArgs( '/wc-pb/v3/products', getQuery( this.props.attributes ) ),
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
		const { columns, orderby, rows } = attributes;

		return (
			<InspectorControls key="inspector">
				<PanelBody
					title={ __( 'Product Category', 'woo-gutenberg-products-block' ) }
					initialOpen={ false }
				>
					<ProductCategoryControl
						selected={ attributes.categories }
						onChange={ ( value = [] ) => {
							const ids = value.map( ( { id } ) => id );
							setAttributes( { categories: ids } );
						} }
					/>
				</PanelBody>
				<PanelBody
					title={ __( 'Layout', 'woo-gutenberg-products-block' ) }
					initialOpen
				>
					<RangeControl
						label={ __( 'Columns', 'woo-gutenberg-products-block' ) }
						value={ columns }
						onChange={ ( value ) => setAttributes( { columns: value } ) }
						min={ wc_product_block_data.min_columns }
						max={ wc_product_block_data.max_columns }
					/>
					<RangeControl
						label={ __( 'Rows', 'woo-gutenberg-products-block' ) }
						value={ rows }
						onChange={ ( value ) => setAttributes( { rows: value } ) }
						min={ wc_product_block_data.min_rows }
						max={ wc_product_block_data.max_rows }
					/>
				</PanelBody>
				<PanelBody
					title={ __( 'Order By', 'woo-gutenberg-products-block' ) }
					initialOpen={ false }
				>
					<SelectControl
						label={ __( 'Order products by', 'woo-gutenberg-products-block' ) }
						value={ orderby }
						options={ [
							{
								label: __(
									'Newness - newest first',
									'woo-gutenberg-products-block'
								),
								value: 'date',
							},
							{
								label: __(
									'Price - low to high',
									'woo-gutenberg-products-block'
								),
								value: 'price_asc',
							},
							{
								label: __(
									'Price - high to low',
									'woo-gutenberg-products-block'
								),
								value: 'price_desc',
							},
							{
								label: __(
									'Rating - highest first',
									'woo-gutenberg-products-block'
								),
								value: 'rating',
							},
							{
								label: __( 'Sales - most first', 'woo-gutenberg-products-block' ),
								value: 'popularity',
							},
							{
								label: __(
									'Title - alphabetical',
									'woo-gutenberg-products-block'
								),
								value: 'title',
							},
							{
								label: __( 'Menu Order', 'woo-gutenberg-products-block' ),
								value: 'menu_order',
							},
						] }
						onChange={ ( value ) => setAttributes( { orderby: value } ) }
					/>
				</PanelBody>
			</InspectorControls>
		);
	}

	renderEditMode() {
		const { attributes, debouncedSpeak, setAttributes } = this.props;
		const onDone = () => {
			setAttributes( { editMode: false } );
			debouncedSpeak(
				__( 'Showing product block preview.', 'woo-gutenberg-products-block' )
			);
		};

		return (
			<Placeholder
				icon="category"
				label={ __( 'Products by Category', 'woo-gutenberg-products-block' ) }
				className="wc-block-products-category"
			>
				{ __(
					'Display a grid of products from your selected categories',
					'woo-gutenberg-products-block'
				) }
				<div className="wc-block-products-category__selection">
					<ProductCategoryControl
						selected={ attributes.categories }
						onChange={ ( value = [] ) => {
							const ids = value.map( ( { id } ) => id );
							setAttributes( { categories: ids } );
						} }
					/>
					<Button isDefault onClick={ onDone }>
						{ __( 'Done', 'woo-gutenberg-products-block' ) }
					</Button>
				</div>
			</Placeholder>
		);
	}

	render() {
		const { setAttributes } = this.props;
		const { columns, align, editMode } = this.props.attributes;
		const { loaded, products } = this.state;
		const classes = [ 'wc-block-products-category' ];
		if ( columns ) {
			classes.push( `cols-${ columns }` );
		}
		if ( products && ! products.length ) {
			if ( ! loaded ) {
				classes.push( 'is-loading' );
			} else {
				classes.push( 'is-not-found' );
			}
		}

		return (
			<Fragment>
				<BlockControls>
					<BlockAlignmentToolbar
						controls={ validAlignments }
						value={ align }
						onChange={ ( nextAlign ) => setAttributes( { align: nextAlign } ) }
					/>
					<Toolbar
						controls={ [
							{
								icon: 'edit',
								title: __( 'Edit' ),
								onClick: () => setAttributes( { editMode: ! editMode } ),
								isActive: editMode,
							},
						] }
					/>
				</BlockControls>
				{ this.getInspectorControls() }
				{ editMode ? (
					this.renderEditMode()
				) : (
					<div className={ classes.join( ' ' ) }>
						{ products.length ? (
							products.map( ( product ) => (
								<ProductPreview product={ product } key={ product.id } />
							) )
						) : (
							<Placeholder
								icon="category"
								label={ __(
									'Products by Category',
									'woo-gutenberg-products-block'
								) }
							>
								{ ! loaded ? (
									<Spinner />
								) : (
									__(
										'No products in this category.',
										'woo-gutenberg-products-block'
									)
								) }
							</Placeholder>
						) }
					</div>
				) }
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
	// from withSpokenMessages
	debouncedSpeak: PropTypes.func.isRequired,
};

const WrappedProductByCategoryBlock = withSpokenMessages(
	ProductByCategoryBlock
);

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
		editMode: {
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
		return <WrappedProductByCategoryBlock { ...props } />;
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
				{ getShortcode( props ) }
			</RawHTML>
		);
	},
} );
