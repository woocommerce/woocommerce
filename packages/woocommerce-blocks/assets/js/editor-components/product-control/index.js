/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { escapeRegExp, isEmpty } from 'lodash';
import PropTypes from 'prop-types';
import { SearchListControl, SearchListItem } from '@woocommerce/components';
import { Spinner, MenuItem } from '@wordpress/components';
import classnames from 'classnames';
import {
	withProductVariations,
	withSearchedProducts,
	withTransformSingleSelectToMultipleSelect,
} from '@woocommerce/block-hocs';
import { Icon, radioSelected, radioUnselected } from '@woocommerce/icons';
import ErrorMessage from '@woocommerce/editor-components/error-placeholder/error-message.js';

/**
 * Internal dependencies
 */
import './style.scss';

function getHighlightedName( name, search ) {
	if ( ! search ) {
		return name;
	}
	const re = new RegExp( escapeRegExp( search ), 'ig' );
	return name.replace( re, '<strong>$&</strong>' );
}

const getInteractionIcon = ( isSelected = false ) => {
	return isSelected ? (
		<Icon srcElement={ radioSelected } />
	) : (
		<Icon srcElement={ radioUnselected } />
	);
};

const messages = {
	list: __( 'Products', 'woocommerce' ),
	noItems: __(
		"Your store doesn't have any products.",
		'woocommerce'
	),
	search: __(
		'Search for a product to display',
		'woocommerce'
	),
	updated: __(
		'Product search results updated.',
		'woocommerce'
	),
};

const ProductControl = ( {
	expandedProduct,
	error,
	isLoading,
	onChange,
	onSearch,
	products,
	renderItem,
	selected,
	showVariations,
	variations,
	variationsLoading,
} ) => {
	const renderItemWithVariations = ( args ) => {
		const { item, search, depth = 0, isSelected, onSelect } = args;
		const variationsCount =
			item.variations && Array.isArray( item.variations )
				? item.variations.length
				: 0;
		const classes = classnames(
			'woocommerce-search-product__item',
			'woocommerce-search-list__item',
			`depth-${ depth }`,
			{
				'is-searching': search.length > 0,
				'is-skip-level': depth === 0 && item.parent !== 0,
				'is-variable': variationsCount > 0,
			}
		);

		const itemArgs = Object.assign( {}, args );
		delete itemArgs.isSingle;

		const a11yProps = {
			role: 'menuitemradio',
		};

		if ( item.breadcrumbs.length ) {
			a11yProps[
				'aria-label'
			] = `${ item.breadcrumbs[ 0 ] }: ${ item.name }`;
		}

		if ( variationsCount ) {
			a11yProps[ 'aria-expanded' ] = item.id === expandedProduct;
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

					{ variationsCount ? (
						<span className="woocommerce-search-list__item-variation-count">
							{ sprintf(
								_n(
									'%d variation',
									'%d variations',
									variationsCount,
									'woocommerce'
								),
								variationsCount
							) }
						</span>
					) : null }
				</MenuItem>,
				expandedProduct === item.id &&
					variationsCount > 0 &&
					variationsLoading && (
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
	};

	const getRenderItemFunc = () => {
		if ( renderItem ) {
			return renderItem;
		} else if ( showVariations ) {
			return renderItemWithVariations;
		}
		return null;
	};

	if ( error ) {
		return <ErrorMessage error={ error } />;
	}

	const currentVariations =
		variations && variations[ expandedProduct ]
			? variations[ expandedProduct ]
			: [];
	const currentList = [ ...products, ...currentVariations ];

	return (
		<SearchListControl
			className="woocommerce-products"
			list={ currentList }
			isLoading={ isLoading }
			isSingle
			selected={ currentList.filter( ( { id } ) =>
				selected.includes( id )
			) }
			onChange={ onChange }
			renderItem={ getRenderItemFunc() }
			onSearch={ onSearch }
			messages={ messages }
			isHierarchical
		/>
	);
};

ProductControl.propTypes = {
	/**
	 * Callback to update the selected products.
	 */
	onChange: PropTypes.func.isRequired,
	/**
	 * The ID of the currently expanded product.
	 */
	expandedProduct: PropTypes.number,
	/**
	 * Callback to search products by their name.
	 */
	onSearch: PropTypes.func,
	/**
	 * Query args to pass to getProducts.
	 */
	queryArgs: PropTypes.object,
	/**
	 * Callback to render each item in the selection list, allows any custom object-type rendering.
	 */
	renderItem: PropTypes.func,
	/**
	 * The ID of the currently selected item (product or variation).
	 */
	selected: PropTypes.arrayOf( PropTypes.number ),
	/**
	 * Whether to show variations in the list of items available.
	 */
	showVariations: PropTypes.bool,
};

ProductControl.defaultProps = {
	expandedProduct: null,
	selected: [],
	showVariations: false,
};

export default withTransformSingleSelectToMultipleSelect(
	withSearchedProducts( withProductVariations( ProductControl ) )
);
