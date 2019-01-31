/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import apiFetch from '@wordpress/api-fetch';
import { InspectorControls } from '@wordpress/editor';
import { Component, Fragment } from '@wordpress/element';
import { debounce } from 'lodash';
import { PanelBody, Placeholder, Spinner } from '@wordpress/components';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import getQuery from '../../utils/get-query';
import GridLayoutControl from '../../components/grid-layout-control';
import { IconNewReleases } from '../../components/icons';
import ProductCategoryControl from '../../components/product-category-control';
import ProductPreview from '../../components/product-preview';

/**
 * Component to handle edit mode of "Newest Products".
 */
class ProductNewestBlock extends Component {
	constructor() {
		super( ...arguments );
		this.state = {
			products: [],
			loaded: false,
		};

		this.debouncedGetProducts = debounce( this.getProducts.bind( this ), 200 );
	}

	componentDidMount() {
		if ( this.props.attributes.categories ) {
			this.getProducts();
		}
	}

	componentDidUpdate( prevProps ) {
		const hasChange = [ 'rows', 'columns', 'categories', 'catOperator' ].reduce( ( acc, key ) => {
			return acc || prevProps.attributes[ key ] !== this.props.attributes[ key ];
		}, false );
		if ( hasChange ) {
			this.debouncedGetProducts();
		}
	}

	getProducts() {
		apiFetch( {
			path: addQueryArgs(
				'/wc-pb/v3/products',
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
		const { categories, catOperator, columns, rows } = attributes;

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
		const { columns } = this.props.attributes;
		const { loaded, products } = this.state;
		const classes = [ 'wc-block-products-grid', 'wc-block-newest-products' ];
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
				{ this.getInspectorControls() }
				<div className={ classes.join( ' ' ) }>
					{ products.length ? (
						products.map( ( product ) => (
							<ProductPreview product={ product } key={ product.id } />
						) )
					) : (
						<Placeholder
							icon={ <IconNewReleases /> }
							label={ __( 'Newest Products', 'woo-gutenberg-products-block' ) }
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

ProductNewestBlock.propTypes = {
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

export default ProductNewestBlock;
