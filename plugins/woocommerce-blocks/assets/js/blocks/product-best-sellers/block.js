/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import apiFetch from '@wordpress/api-fetch';
import classnames from 'classnames';
import { Component, Fragment } from '@wordpress/element';
import { debounce } from 'lodash';
import Gridicon from 'gridicons';
import { InspectorControls } from '@wordpress/editor';
import { PanelBody, Placeholder, Spinner } from '@wordpress/components';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import getQuery from '../../utils/get-query';
import GridContentControl from '../../components/grid-content-control';
import GridLayoutControl from '../../components/grid-layout-control';
import ProductCategoryControl from '../../components/product-category-control';
import ProductPreview from '../../components/product-preview';

/**
 * Component to handle edit mode of "Best Selling Products".
 */
class ProductBestSellersBlock extends Component {
	constructor() {
		super( ...arguments );
		this.state = {
			products: [],
			loaded: false,
		};

		this.debouncedGetProducts = debounce( this.getProducts.bind( this ), 200 );
	}

	componentDidMount() {
		this.getProducts();
	}

	componentDidUpdate( prevProps ) {
		const hasChange = [ 'categories', 'catOperator', 'columns', 'rows' ].reduce(
			( acc, key ) => {
				return acc || prevProps.attributes[ key ] !== this.props.attributes[ key ];
			},
			false
		);
		if ( hasChange ) {
			this.debouncedGetProducts();
		}
	}

	getProducts() {
		apiFetch( {
			path: addQueryArgs(
				'/wc-blocks/v1/products',
				getQuery( this.props.attributes, this.props.name )
			),
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
		const {
			categories,
			catOperator,
			columns,
			contentVisibility,
			rows,
		} = attributes;

		return (
			<InspectorControls key="inspector">
				<PanelBody
					title={ __( 'Layout', 'woo-gutenberg-products-block' ) }
					initialOpen
				>
					<GridLayoutControl
						columns={ columns }
						rows={ rows }
						setAttributes={ setAttributes }
					/>
				</PanelBody>
				<PanelBody
					title={ __( 'Content', 'woo-gutenberg-products-block' ) }
					initialOpen
				>
					<GridContentControl
						settings={ contentVisibility }
						onChange={ ( value ) => setAttributes( { contentVisibility: value } ) }
					/>
				</PanelBody>
				<PanelBody
					title={ __(
						'Filter by Product Category',
						'woo-gutenberg-products-block'
					) }
					initialOpen={ false }
				>
					<ProductCategoryControl
						selected={ categories }
						onChange={ ( value = [] ) => {
							const ids = value.map( ( { id } ) => id );
							setAttributes( { categories: ids } );
						} }
						operator={ catOperator }
						onOperatorChange={ ( value = 'any' ) =>
							setAttributes( { catOperator: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>
		);
	}

	render() {
		const { columns, contentVisibility } = this.props.attributes;
		const { loaded, products = [] } = this.state;
		const classes = classnames( {
			'wc-block-products-grid': true,
			'wc-block-best-selling-products': true,
			[ `cols-${ columns }` ]: columns,
			'is-loading': ! loaded,
			'is-not-found': loaded && ! products.length,
			'is-hidden-title': ! contentVisibility.title,
			'is-hidden-price': ! contentVisibility.price,
			'is-hidden-rating': ! contentVisibility.rating,
			'is-hidden-button': ! contentVisibility.button,
		} );

		return (
			<Fragment>
				{ this.getInspectorControls() }
				<div className={ classes }>
					{ products.length ? (
						products.map( ( product ) => (
							<ProductPreview product={ product } key={ product.id } />
						) )
					) : (
						<Placeholder
							icon={ <Gridicon icon="stats-up-alt" /> }
							label={ __(
								'Best Selling Products',
								'woo-gutenberg-products-block'
							) }
						>
							{ ! loaded ? (
								<Spinner />
							) : (
								__( 'No products found.', 'woo-gutenberg-products-block' )
							) }
						</Placeholder>
					) }
				</div>
			</Fragment>
		);
	}
}

ProductBestSellersBlock.propTypes = {
	/**
	 * The attributes for this block
	 */
	attributes: PropTypes.object.isRequired,
	/**
	 * The register block name.
	 */
	name: PropTypes.string.isRequired,
	/**
	 * A callback to update attributes
	 */
	setAttributes: PropTypes.func.isRequired,
};

export default ProductBestSellersBlock;
