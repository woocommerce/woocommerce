/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { debounce, find } from 'lodash';
import PropTypes from 'prop-types';
import { SearchListControl, SearchListItem } from '@woocommerce/components';
import { SelectControl } from '@wordpress/components';
import { LIMIT_TAGS } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import { getProductTags } from '../utils';
import './style.scss';

/**
 * Component to handle searching and selecting product tags.
 */
class ProductTagControl extends Component {
	constructor() {
		super( ...arguments );
		this.state = {
			list: [],
			loading: true,
		};
		this.renderItem = this.renderItem.bind( this );
		this.debouncedOnSearch = debounce( this.onSearch.bind( this ), 400 );
	}

	componentDidMount() {
		const { selected } = this.props;

		getProductTags( { selected } )
			.then( ( list ) => {
				this.setState( { list, loading: false } );
			} )
			.catch( () => {
				this.setState( { list: [], loading: false } );
			} );
	}

	onSearch( search ) {
		const { selected } = this.props;
		this.setState( { loading: true } );

		getProductTags( { selected, search } )
			.then( ( list ) => {
				this.setState( { list, loading: false } );
			} )
			.catch( () => {
				this.setState( { list: [], loading: false } );
			} );
	}

	renderItem( args ) {
		const { item, search, depth = 0 } = args;
		const classes = [ 'woocommerce-product-tags__item' ];
		if ( search.length ) {
			classes.push( 'is-searching' );
		}
		if ( depth === 0 && item.parent !== 0 ) {
			classes.push( 'is-skip-level' );
		}

		const accessibleName = ! item.breadcrumbs.length
			? item.name
			: `${ item.breadcrumbs.join( ', ' ) }, ${ item.name }`;

		return (
			<SearchListItem
				className={ classes.join( ' ' ) }
				{ ...args }
				showCount
				aria-label={ sprintf(
					_n(
						'%d product tagged as %s',
						'%d products tagged as %s',
						item.count,
						'woocommerce'
					),
					item.count,
					accessibleName
				) }
			/>
		);
	}

	render() {
		const { list, loading } = this.state;
		const { onChange, onOperatorChange, operator, selected } = this.props;

		const messages = {
			clear: __(
				'Clear all product tags',
				'woocommerce'
			),
			list: __( 'Product Tags', 'woocommerce' ),
			noItems: __(
				"Your store doesn't have any product tags.",
				'woocommerce'
			),
			search: __(
				'Search for product tags',
				'woocommerce'
			),
			selected: ( n ) =>
				sprintf(
					_n(
						'%d tag selected',
						'%d tags selected',
						n,
						'woocommerce'
					),
					n
				),
			updated: __(
				'Tag search results updated.',
				'woocommerce'
			),
		};

		return (
			<Fragment>
				<SearchListControl
					className="woocommerce-product-tags"
					list={ list }
					isLoading={ loading }
					selected={ selected
						.map( ( id ) => find( list, { id } ) )
						.filter( Boolean ) }
					onChange={ onChange }
					onSearch={ LIMIT_TAGS ? this.debouncedOnSearch : null }
					renderItem={ this.renderItem }
					messages={ messages }
					isHierarchical
				/>
				{ !! onOperatorChange && (
					<div
						className={
							selected.length < 2 ? 'screen-reader-text' : ''
						}
					>
						<SelectControl
							className="woocommerce-product-tags__operator"
							label={ __(
								'Display products matching',
								'woocommerce'
							) }
							help={ __(
								'Pick at least two tags to use this setting.',
								'woocommerce'
							) }
							value={ operator }
							onChange={ onOperatorChange }
							options={ [
								{
									label: __(
										'Any selected tags',
										'woocommerce'
									),
									value: 'any',
								},
								{
									label: __(
										'All selected tags',
										'woocommerce'
									),
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

ProductTagControl.propTypes = {
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
	 * The list of currently selected tags.
	 */
	selected: PropTypes.array.isRequired,
};

ProductTagControl.defaultProps = {
	operator: 'any',
};

export default ProductTagControl;
