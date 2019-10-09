/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import apiFetch from '@wordpress/api-fetch';
import { Component, Fragment } from '@wordpress/element';
import { find } from 'lodash';
import PropTypes from 'prop-types';
import { SearchListControl, SearchListItem } from '@woocommerce/components';
import { SelectControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import './style.scss';

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
			path: addQueryArgs( '/wc/blocks/products/categories', { per_page: -1 } ),
		} )
			.then( ( list ) => {
				this.setState( { list, loading: false } );
			} )
			.catch( () => {
				this.setState( { list: [], loading: false } );
			} );
	}

	renderItem( args ) {
		const { item, search, depth = 0 } = args;
		const classes = [
			'woocommerce-product-categories__item',
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
			<SearchListItem
				className={ classes.join( ' ' ) }
				{ ...args }
				showCount
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
			/>
		);
	}

	render() {
		const { list, loading } = this.state;
		const { onChange, onOperatorChange, operator, selected, isSingle } = this.props;

		const messages = {
			clear: __( 'Clear all product categories', 'woo-gutenberg-products-block' ),
			list: __( 'Product Categories', 'woo-gutenberg-products-block' ),
			noItems: __(
				"Your store doesn't have any product categories.",
				'woo-gutenberg-products-block'
			),
			search: __(
				'Search for product categories',
				'woo-gutenberg-products-block'
			),
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
			updated: __(
				'Category search results updated.',
				'woo-gutenberg-products-block'
			),
		};

		return (
			<Fragment>
				<SearchListControl
					className="woocommerce-product-categories"
					list={ list }
					isLoading={ loading }
					selected={ selected.map( ( id ) => find( list, { id } ) ).filter( Boolean ) }
					onChange={ onChange }
					renderItem={ this.renderItem }
					messages={ messages }
					isHierarchical
					isSingle={ isSingle }
				/>
				{ ( !! onOperatorChange ) && (
					<div className={ selected.length < 2 ? 'screen-reader-text' : '' }>
						<SelectControl
							className="woocommerce-product-categories__operator"
							label={ __( 'Display products matching', 'woo-gutenberg-products-block' ) }
							help={ __( 'Pick at least two categories to use this setting.', 'woo-gutenberg-products-block' ) }
							value={ operator }
							onChange={ onOperatorChange }
							options={ [
								{
									label: __( 'Any selected categories', 'woo-gutenberg-products-block' ),
									value: 'any',
								},
								{
									label: __( 'All selected categories', 'woo-gutenberg-products-block' ),
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

ProductCategoryControl.propTypes = {
	/**
	 * Callback to update the selected product categories.
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
	 * The list of currently selected category IDs.
	 */
	selected: PropTypes.array.isRequired,
	/**
	 * Allow only a single selection. Defaults to false.
	 */
	isSingle: PropTypes.bool,
};

ProductCategoryControl.defaultProps = {
	operator: 'any',
	isSingle: false,
};

export default ProductCategoryControl;
