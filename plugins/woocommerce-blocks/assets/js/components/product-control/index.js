/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { addQueryArgs } from '@wordpress/url';
import apiFetch from '@wordpress/api-fetch';
import { debounce, find, escapeRegExp, isEmpty } from 'lodash';
import PropTypes from 'prop-types';
import {
	SearchListControl,
	SearchListItem,
} from '@woocommerce/components';
import { Spinner, MenuItem } from '@wordpress/components';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import { isLargeCatalog, getProducts } from '../utils';
import {
	IconRadioSelected,
	IconRadioUnselected,
} from '../icons';
import './style.scss';

function getHighlightedName( name, search ) {
	if ( ! search ) {
		return name;
	}
	const re = new RegExp( escapeRegExp( search ), 'ig' );
	return name.replace( re, '<strong>$&</strong>' );
}

const getInteractionIcon = ( isSelected = false ) => {
	return isSelected ? <IconRadioSelected /> : <IconRadioUnselected />;
};

class ProductControl extends Component {
	constructor() {
		super( ...arguments );
		this.state = {
			products: [],
			product: 0,
			variationsList: {},
			variationsLoading: false,
			loading: true,
		};

		this.debouncedOnSearch = debounce( this.onSearch.bind( this ), 400 );
		this.debouncedGetVariations = debounce( this.getVariations.bind( this ), 200 );
		this.renderItem = this.renderItem.bind( this );
		this.onProductSelect = this.onProductSelect.bind( this );
	}

	componentDidMount() {
		const { selected } = this.props;

		getProducts( { selected } )
			.then( ( products ) => {
				products = products.map( ( product ) => {
					const count = product.variations ? product.variations.length : 0;
					return {
						...product,
						parent: 0,
						count: count,
					};
				} );
				this.setState( { products, loading: false } );
			} )
			.catch( () => {
				this.setState( { products: [], loading: false } );
			} );
	}

	componentDidUpdate( prevProps, prevState ) {
		if ( prevState.product !== this.state.product ) {
			this.debouncedGetVariations();
		}
	}

	getVariations() {
		const { product, variationsList } = this.state;

		if ( ! product ) {
			this.setState( {
				variationsList: {},
				variationsLoading: false,
			} );
			return;
		}

		const productDetails = this.state.products.find( ( findProduct ) => findProduct.id === product );

		if ( ! productDetails.variations || productDetails.variations.length === 0 ) {
			return;
		}

		if ( ! variationsList[ product ] ) {
			this.setState( { variationsLoading: true } );
		}

		apiFetch( {
			path: addQueryArgs( `/wc/blocks/products/${ product }/variations`, {
				per_page: -1,
			} ),
		} )
			.then( ( variations ) => {
				variations = variations.map( ( variation ) => ( { ...variation, parent: product } ) );
				this.setState( ( prevState ) => ( {
					variationsList: { ...prevState.variationsList, [ product ]: variations },
					variationsLoading: false,
				} ) );
			} )
			.catch( () => {
				this.setState( { termsLoading: false } );
			} );
	}

	onSearch( search ) {
		const { selected } = this.props;
		getProducts( { selected, search } )
			.then( ( products ) => {
				this.setState( { products, loading: false } );
			} )
			.catch( () => {
				this.setState( { products: [], loading: false } );
			} );
	}

	onProductSelect( item, isSelected ) {
		return () => {
			this.setState( {
				product: isSelected ? 0 : item.id,
			} );
		};
	}

	renderItem( args ) {
		const { item, search, depth = 0, isSelected, onSelect } = args;
		const { product, variationsLoading } = this.state;
		const classes = classnames(
			'woocommerce-search-product__item',
			'woocommerce-search-list__item',
			`depth-${ depth }`,
			{
				'is-searching': search.length > 0,
				'is-skip-level': depth === 0 && item.parent !== 0,
				'is-variable': item.count > 0,
			}
		);

		const itemArgs = Object.assign( {}, args );
		delete itemArgs.isSingle;

		const a11yProps = {
			role: 'menuitemradio',
		};

		if ( item.breadcrumbs.length ) {
			a11yProps[ 'aria-label' ] = `${ item.breadcrumbs[ 0 ] }: ${ item.name }`;
		}

		if ( item.count ) {
			a11yProps[ 'aria-expanded' ] = item.id === product;
		}

		// Top level items custom rendering based on SearchListItem.
		if ( ! item.breadcrumbs.length ) {
			return [
				<MenuItem
					key={ `product-${ item.id }` }
					isSelected={ isSelected }
					{ ...itemArgs }
					{ ...a11yProps }
					className={ classes }
					onClick={ () => {
						onSelect( item )();
						this.onProductSelect( item, isSelected )();
					} }
				>
					<span className="woocommerce-search-list__item-state">
						{ getInteractionIcon( isSelected ) }
					</span>

					<span className="woocommerce-search-list__item-label">
						<span
							className="woocommerce-search-list__item-name"
							dangerouslySetInnerHTML={ {
								__html: getHighlightedName( item.name, search ),
							} }
						/>
					</span>

					{ item.count ? (
						<span
							className="woocommerce-search-list__item-variation-count"
						>
							{ sprintf(
								_n(
									'%d variation',
									'%d variations',
									item.count,
									'woo-gutenberg-products-block'
								),
								item.count
							) }
						</span>
					) : null }
				</MenuItem>,
				product === item.id && item.count > 0 && variationsLoading && (
					<div
						key="loading"
						className={
							'woocommerce-search-list__item woocommerce-search-product__item' +
							'depth-1 is-loading is-not-active'
						}
					>
						<Spinner />
					</div>
				),
			];
		}

		if ( ! isEmpty( item.variation ) ) {
			item.name = item.variation;
		}

		return (
			<SearchListItem
				className={ classes }
				{ ...args }
				{ ...a11yProps }
			/>
		);
	}

	render() {
		const { products, loading, product, variationsList } = this.state;
		const { onChange, selected } = this.props;
		const currentVariations = variationsList[ product ] || [];
		const currentList = [ ...products, ...currentVariations ];
		const messages = {
			list: __( 'Products', 'woo-gutenberg-products-block' ),
			noItems: __(
				"Your store doesn't have any products.",
				'woo-gutenberg-products-block'
			),
			search: __(
				'Search for a product to display',
				'woo-gutenberg-products-block'
			),
			updated: __(
				'Product search results updated.',
				'woo-gutenberg-products-block'
			),
		};
		const selectedListItems = selected ? [ find( currentList, { id: selected } ) ] : [];

		return (
			<Fragment>
				<SearchListControl
					className="woocommerce-products"
					list={ currentList }
					isLoading={ loading }
					isSingle
					selected={ selectedListItems }
					onChange={ onChange }
					onSearch={ isLargeCatalog ? this.debouncedOnSearch : null }
					messages={ messages }
					renderItem={ this.renderItem }
					isHierarchical
				/>
			</Fragment>
		);
	}
}

ProductControl.propTypes = {
	/**
	 * Callback to update the selected products.
	 */
	onChange: PropTypes.func.isRequired,
	/**
	 * The ID of the currently selected product.
	 */
	selected: PropTypes.number.isRequired,
};

export default ProductControl;
