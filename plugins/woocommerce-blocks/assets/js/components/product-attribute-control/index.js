/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import apiFetch from '@wordpress/api-fetch';
import { Component, Fragment } from '@wordpress/element';
import { find } from 'lodash';
import PropTypes from 'prop-types';
import { SelectControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import './style.scss';
import SearchListControl from '../search-list-control';
import SearchListItem from '../search-list-control/item';

class ProductAttributeControl extends Component {
	constructor() {
		super( ...arguments );
		this.state = {
			list: [],
			loading: true,
		};
	}

	componentDidMount() {
		const getTermsInAttribute = ( { id } ) => {
			return apiFetch( {
				path: addQueryArgs( `/wc-pb/v3/products/attributes/${ id }/terms`, {
					per_page: -1,
				} ),
			} ).then( ( terms ) => terms.map( ( t ) => ( { ...t, parent: id } ) ) );
		};

		apiFetch( {
			path: addQueryArgs( '/wc-pb/v3/products/attributes', { per_page: -1 } ),
		} )
			.then( ( attributes ) => {
				// Fetch the terms list for each attribute group, then flatten them into one list.
				Promise.all( attributes.map( getTermsInAttribute ) ).then( ( results ) => {
					const list = attributes.map( ( a ) => ( { ...a, parent: 0 } ) );
					results.forEach( ( terms ) => {
						list.push( ...terms );
					} );
					this.setState( { list, loading: false } );
				} );
			} )
			.catch( () => {
				this.setState( { list: [], loading: false } );
			} );
	}

	renderItem( args ) {
		const { item, search, depth = 0 } = args;
		const classes = [
			'woocommerce-product-attributes__item',
			'woocommerce-search-list__item',
		];
		if ( search.length ) {
			classes.push( 'is-searching' );
		}
		if ( depth === 0 && item.parent !== 0 ) {
			classes.push( 'is-skip-level' );
		}

		if ( ! item.breadcrumbs.length ) {
			classes.push( 'is-not-active' );
			return (
				<div className={ classes.join( ' ' ) }>
					<span className="woocommerce-search-list__item-label">
						<span className="woocommerce-search-list__item-name">
							{ item.name }
						</span>
					</span>
				</div>
			);
		}

		return (
			<SearchListItem
				className={ classes.join( ' ' ) }
				{ ...args }
				showCount
				aria-label={ `${ item.breadcrumbs[ 0 ] }: ${ item.name }` }
			/>
		);
	}

	render() {
		const { list, loading } = this.state;
		const { onChange, onOperatorChange, operator = 'any', selected } = this.props;

		const messages = {
			clear: __( 'Clear all product attributes', 'woo-gutenberg-products-block' ),
			list: __( 'Product Attributes', 'woo-gutenberg-products-block' ),
			noItems: __(
				"Your store doesn't have any product attributes.",
				'woo-gutenberg-products-block'
			),
			search: __(
				'Search for product attributes',
				'woo-gutenberg-products-block'
			),
			selected: ( n ) =>
				sprintf(
					_n(
						'%d attribute selected',
						'%d attributes selected',
						n,
						'woo-gutenberg-products-block'
					),
					n
				),
			updated: __(
				'Product attribute search results updated.',
				'woo-gutenberg-products-block'
			),
		};

		return (
			<Fragment>
				<SearchListControl
					className="woocommerce-product-attributes"
					list={ list }
					isLoading={ loading }
					selected={ selected.map( ( { id } ) => find( list, { id } ) ).filter( Boolean ) }
					onChange={ onChange }
					renderItem={ this.renderItem }
					messages={ messages }
					isHierarchical
				/>
				{ ( !! onOperatorChange ) && (
					<div className={ selected.length < 2 ? 'screen-reader-text' : '' }>
						<SelectControl
							className="woocommerce-product-attributes__operator"
							label={ __( 'Display products matching', 'woo-gutenberg-products-block' ) }
							help={ __( 'Pick at least two attributes to use this setting.', 'woo-gutenberg-products-block' ) }
							value={ operator }
							onChange={ onOperatorChange }
							options={ [
								{
									label: __( 'Any selected attributes', 'woo-gutenberg-products-block' ),
									value: 'any',
								},
								{
									label: __( 'All selected attributes', 'woo-gutenberg-products-block' ),
									value: 'all',
								},
							] }
						/>
					</div>
				) }
			</Fragment>
		);
	}
}

ProductAttributeControl.propTypes = {
	/**
	 * Callback to update the selected product attributes.
	 */
	onChange: PropTypes.func.isRequired,
	/**
	 * Callback to update the category operator. If not passed in, setting is not used.
	 */
	onOperatorChange: PropTypes.func,
	/**
	 * Setting for whether products should match all or any selected categories.
	 */
	operator: PropTypes.oneOf( [ 'all', 'any' ] ),
	/**
	 * The list of currently selected attribute slug/ID pairs.
	 */
	selected: PropTypes.array.isRequired,
};

export default ProductAttributeControl;
