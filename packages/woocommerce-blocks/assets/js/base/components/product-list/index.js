/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { isEqual } from 'lodash';
import PropTypes from 'prop-types';
import classnames from 'classnames';
import Pagination from '@woocommerce/base-components/pagination';
import ProductSortSelect from '@woocommerce/base-components/product-sort-select';
import ProductListItem from '@woocommerce/base-components/product-list-item';
import { useEffect } from '@wordpress/element';
import {
	usePrevious,
	useStoreProducts,
	useSynchronizedQueryState,
	useQueryStateByKey,
} from '@woocommerce/base-hooks';
import withScrollToTop from '@woocommerce/base-hocs/with-scroll-to-top';
import { useInnerBlockLayoutContext } from '@woocommerce/shared-context';
import { speak } from '@wordpress/a11y';

/**
 * Internal dependencies
 */
import './style.scss';
import NoProducts from './no-products';
import NoMatchingProducts from './no-matching-products';

const generateQuery = ( { sortValue, currentPage, attributes } ) => {
	const { columns, rows } = attributes;
	const getSortArgs = ( orderName ) => {
		switch ( orderName ) {
			case 'menu_order':
			case 'popularity':
			case 'rating':
			case 'price':
				return {
					orderby: orderName,
					order: 'asc',
				};
			case 'price-desc':
				return {
					orderby: 'price',
					order: 'desc',
				};
			case 'date':
				return {
					orderby: 'date',
					order: 'desc',
				};
		}
	};
	return {
		...getSortArgs( sortValue ),
		catalog_visibility: 'catalog',
		per_page: columns * rows,
		page: currentPage,
	};
};

/**
 * Given a query state, returns the same query without the attributes related to
 * pagination and sorting.
 *
 * @param {Object} query Query to extract the attributes from.
 *
 * @return {Object} Same query without pagination and sorting attributes.
 */

const extractPaginationAndSortAttributes = ( query ) => {
	/* eslint-disable-next-line no-unused-vars, camelcase */
	const { order, orderby, page, per_page, ...totalQuery } = query;
	return totalQuery || {};
};

const announceLoadingCompletion = ( totalProducts ) => {
	if ( ! Number.isFinite( totalProducts ) ) {
		return;
	}

	if ( totalProducts === 0 ) {
		speak( __( 'No products found', 'woocommerce' ) );
	} else {
		speak(
			sprintf(
				// translators: %s is an integer higher than 0 (1, 2, 3...)
				_n(
					'%d product found',
					'%d products found',
					totalProducts,
					'woocommerce'
				),
				totalProducts
			)
		);
	}
};

const areQueryTotalsDifferent = (
	{ totalQuery: nextQuery, totalProducts: nextProducts },
	{ totalQuery: currentQuery } = {}
) => ! isEqual( nextQuery, currentQuery ) && Number.isFinite( nextProducts );

const ProductList = ( {
	attributes,
	currentPage,
	onPageChange,
	onSortChange,
	sortValue,
	scrollToTop,
} ) => {
	const [ queryState ] = useSynchronizedQueryState(
		generateQuery( {
			attributes,
			sortValue,
			currentPage,
		} )
	);
	const { products, totalProducts, productsLoading } = useStoreProducts(
		queryState
	);
	const { parentClassName } = useInnerBlockLayoutContext();
	const totalQuery = extractPaginationAndSortAttributes( queryState );

	// These are possible filters.
	const [ productAttributes, setProductAttributes ] = useQueryStateByKey(
		'attributes',
		[]
	);
	const [ minPrice, setMinPrice ] = useQueryStateByKey( 'min_price' );
	const [ maxPrice, setMaxPrice ] = useQueryStateByKey( 'max_price' );

	// Only update previous query totals if the query is different and
	// the total number of products is a finite number.
	const previousQueryTotals = usePrevious(
		{ totalQuery, totalProducts },
		areQueryTotalsDifferent
	);

	// If query state (excluding pagination/sorting attributes) changed,
	// reset pagination to the first page.
	useEffect( () => {
		if ( isEqual( totalQuery, previousQueryTotals?.totalQuery ) ) {
			return;
		}
		onPageChange( 1 );

		// Make sure there was a previous query, so we don't announce it on page load.
		if ( previousQueryTotals?.totalQuery ) {
			announceLoadingCompletion( totalProducts );
		}
	}, [
		previousQueryTotals?.totalQuery,
		totalProducts,
		onPageChange,
		totalQuery,
	] );

	const onPaginationChange = ( newPage ) => {
		scrollToTop( { focusableSelector: 'a, button' } );
		onPageChange( newPage );
	};

	const getClassnames = () => {
		const { columns, rows, alignButtons, align } = attributes;
		const alignClass = typeof align !== 'undefined' ? 'align' + align : '';

		return classnames(
			parentClassName,
			alignClass,
			'has-' + columns + '-columns',
			{
				'has-multiple-rows': rows > 1,
				'has-aligned-buttons': alignButtons,
			}
		);
	};

	const { contentVisibility } = attributes;
	const perPage = attributes.columns * attributes.rows;
	const totalPages =
		! Number.isFinite( totalProducts ) &&
		Number.isFinite( previousQueryTotals?.totalProducts ) &&
		isEqual( totalQuery, previousQueryTotals?.totalQuery )
			? Math.ceil( previousQueryTotals.totalProducts / perPage )
			: Math.ceil( totalProducts / perPage );
	const listProducts = products.length
		? products
		: Array.from( { length: perPage } );
	const hasProducts = products.length !== 0 || productsLoading;
	const hasFilters =
		productAttributes.length > 0 ||
		Number.isFinite( minPrice ) ||
		Number.isFinite( maxPrice );

	return (
		<div className={ getClassnames() }>
			{ contentVisibility.orderBy && hasProducts && (
				<ProductSortSelect
					onChange={ onSortChange }
					value={ sortValue }
				/>
			) }
			{ ! hasProducts && hasFilters && (
				<NoMatchingProducts
					resetCallback={ () => {
						setProductAttributes( [] );
						setMinPrice( null );
						setMaxPrice( null );
					} }
				/>
			) }
			{ ! hasProducts && ! hasFilters && <NoProducts /> }
			{ hasProducts && (
				<ul className={ `${ parentClassName }__products` }>
					{ listProducts.map( ( product = {}, i ) => (
						<ProductListItem
							key={ product.id || i }
							attributes={ attributes }
							product={ product }
						/>
					) ) }
				</ul>
			) }
			{ totalPages > 1 && (
				<Pagination
					currentPage={ currentPage }
					onPageChange={ onPaginationChange }
					totalPages={ totalPages }
				/>
			) }
		</div>
	);
};

ProductList.propTypes = {
	attributes: PropTypes.object.isRequired,
	// From withScrollToTop.
	scrollToTop: PropTypes.func,
};

export default withScrollToTop( ProductList );
