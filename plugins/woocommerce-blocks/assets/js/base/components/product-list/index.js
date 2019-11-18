/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import classnames from 'classnames';
import Pagination from '@woocommerce/base-components/pagination';
import ProductSortSelect from '@woocommerce/base-components/product-sort-select';
import ProductListItem from '@woocommerce/base-components/product-list-item';
import {
	useStoreProducts,
	useSynchronizedQueryState,
} from '@woocommerce/base-hooks';
import withScrollToTop from '@woocommerce/base-hocs/with-scroll-to-top';
import { useProductLayoutContext } from '@woocommerce/base-context/product-layout-context';

/**
 * Internal dependencies
 */
import './style.scss';

const generateQuery = ( { sortValue, currentPage, attributes } ) => {
	const { columns, rows } = attributes;
	const getSortArgs = ( orderName ) => {
		switch ( orderName ) {
			case 'menu_order':
			case 'popularity':
			case 'rating':
			case 'date':
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
		}
	};
	return {
		...getSortArgs( sortValue ),
		per_page: columns * rows,
		page: currentPage,
	};
};

const ProductList = ( {
	attributes,
	currentPage,
	onPageChange,
	onSortChange,
	sortValue,
	scrollToTop,
} ) => {
	const [ queryState ] = useSynchronizedQueryState(
		generateQuery( { attributes, sortValue, currentPage } )
	);
	const { products, totalProducts } = useStoreProducts( queryState );
	const { layoutStyleClassPrefix } = useProductLayoutContext();
	const onPaginationChange = ( newPage ) => {
		scrollToTop( { focusableSelector: 'a, button' } );
		onPageChange( newPage );
	};

	const getClassnames = () => {
		const { columns, rows, className, alignButtons, align } = attributes;
		const alignClass = typeof align !== 'undefined' ? 'align' + align : '';

		return classnames(
			layoutStyleClassPrefix,
			className,
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
	const totalPages = Math.ceil( totalProducts / perPage );
	const listProducts = products.length
		? products
		: Array.from( { length: perPage } );

	return (
		<div className={ getClassnames() }>
			{ contentVisibility.orderBy && (
				<ProductSortSelect
					onChange={ onSortChange }
					value={ sortValue }
				/>
			) }
			<ul className={ `${ layoutStyleClassPrefix }__products` }>
				{ listProducts.map( ( product = {}, i ) => (
					<ProductListItem
						key={ product.id || i }
						attributes={ attributes }
						product={ product }
					/>
				) ) }
			</ul>
			{ totalProducts > perPage && (
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
