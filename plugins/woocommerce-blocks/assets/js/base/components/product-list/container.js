/**
 * External dependencies
 */
import { useState, useEffect } from '@wordpress/element';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import ProductList from './product-list';

const ProductListContainer = ( {
	attributes,
	hideOutOfStockItems = false,
} ) => {
	const [ currentPage, setPage ] = useState( 1 );
	const [ currentSort, setSort ] = useState( attributes.orderby );
	useEffect( () => {
		// if default sort is changed in editor
		setSort( attributes.orderby );
	}, [ attributes.orderby ] );
	const onPageChange = ( newPage ) => {
		setPage( newPage );
	};
	const onSortChange = ( event ) => {
		const newSortValue = event.target.value;
		setSort( newSortValue );
		setPage( 1 );
	};

	return (
		<ProductList
			attributes={ attributes }
			hideOutOfStockItems={ hideOutOfStockItems }
			currentPage={ currentPage }
			onPageChange={ onPageChange }
			onSortChange={ onSortChange }
			sortValue={ currentSort }
		/>
	);
};

ProductListContainer.propTypes = {
	attributes: PropTypes.object.isRequired,
	hideOutOfStockItems: PropTypes.bool,
};

export default ProductListContainer;
