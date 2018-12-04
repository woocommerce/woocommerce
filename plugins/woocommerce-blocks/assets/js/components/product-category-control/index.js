/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import apiFetch from '@wordpress/api-fetch';
import { Component } from '@wordpress/element';
import { find, first, last } from 'lodash';
import { MenuItem } from '@wordpress/components';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import './style.scss';
import { CheckedIcon, UncheckedIcon } from '../search-list-control/icons';
import SearchListControl from '../search-list-control';

class ProductCategoryControl extends Component {
	constructor() {
		super( ...arguments );
		this.state = {
			list: [],
			loading: true,
		};
		this.renderItem = this.renderItem.bind( this );
	}

	componentDidMount() {
		apiFetch( {
			path: addQueryArgs( '/wc-pb/v3/products/categories', { per_page: -1 } ),
		} )
			.then( ( list ) => {
				this.setState( { list, loading: false } );
			} )
			.catch( () => {
				this.setState( { list: [], loading: false } );
			} );
	}

	getBreadcrumbsForDisplay( breadcrumbs ) {
		if ( breadcrumbs.length === 1 ) {
			return first( breadcrumbs );
		}
		if ( breadcrumbs.length === 2 ) {
			return first( breadcrumbs ) + ' › ' + last( breadcrumbs );
		}

		return first( breadcrumbs ) + ' … ' + last( breadcrumbs );
	}

	renderItem( { getHighlightedName, isSelected, item, onSelect, search, depth = 0 } ) {
		const classes = [
			'woocommerce-search-list__item',
			'woocommerce-product-categories__item',
			`depth-${ depth }`,
		];
		if ( search.length ) {
			classes.push( 'is-searching' );
		}
		if ( depth === 0 && item.parent !== 0 ) {
			classes.push( 'is-skip-level' );
		}

		const accessibleName = ! item.breadcrumbs.length ?
			item.name :
			`${ item.breadcrumbs.join( ', ' ) }, ${ item.name }`;

		return (
			<MenuItem
				key={ item.id }
				role="menuitemcheckbox"
				className={ classes.join( ' ' ) }
				onClick={ onSelect( item ) }
				isSelected={ isSelected }
				aria-label={ sprintf(
					_n(
						'%s, has %d product',
						'%s, has %d products',
						item.count,
						'woo-gutenberg-products-block'
					),
					accessibleName,
					item.count
				) }
			>
				<span className="woocommerce-search-list__item-state">
					{ isSelected ? <CheckedIcon /> : <UncheckedIcon /> }
				</span>
				<span className="woocommerce-product-categories__item-label">
					{ !! item.breadcrumbs.length && (
						<span className="woocommerce-product-categories__item-prefix">
							{ this.getBreadcrumbsForDisplay( item.breadcrumbs ) }
						</span>
					) }
					<span
						className="woocommerce-product-categories__item-name"
						dangerouslySetInnerHTML={ {
							__html: getHighlightedName( item.name, search ),
						} }
					/>
				</span>
				<span className="woocommerce-product-categories__item-count">
					{ item.count }
				</span>
			</MenuItem>
		);
	}

	render() {
		const { list, loading } = this.state;
		const { selected, onChange } = this.props;

		const messages = {
			clear: __( 'Clear all product categories', 'woo-gutenberg-products-block' ),
			list: __( 'Product Categories', 'woo-gutenberg-products-block' ),
			noItems: __( 'Your store doesn\'t have any product categories.', 'woo-gutenberg-products-block' ),
			search: __( 'Search for product categories', 'woo-gutenberg-products-block' ),
			selected: ( n ) =>
				sprintf(
					_n(
						'%d category selected',
						'%d categories selected',
						n,
						'woo-gutenberg-products-block'
					),
					n
				),
			updated: __( 'Category search results updated.', 'woo-gutenberg-products-block' ),
		};

		return (
			<SearchListControl
				className="woocommerce-product-categories"
				list={ list }
				isLoading={ loading }
				selected={ selected.map( ( id ) => find( list, { id } ) ).filter( Boolean ) }
				onChange={ onChange }
				renderItem={ this.renderItem }
				messages={ messages }
				isHierarchical
			/>
		);
	}
}

ProductCategoryControl.propTypes = {
	/**
	 * Callback to update the selected product categories.
	 */
	onChange: PropTypes.func.isRequired,
	/**
	 * The list of currently selected category IDs.
	 */
	selected: PropTypes.array.isRequired,
};

export default ProductCategoryControl;
