/**
 * External dependencies
 */
import { useState, useEffect } from '@wordpress/element';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import ProductList from './index';

const ProductListContainer = ( { attributes } ) => {
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
			currentPage={ currentPage }
			onPageChange={ onPageChange }
			onSortChange={ onSortChange }
			sortValue={ currentSort }
		/>
	);
};

ProductListContainer.propTypes = {
	attributes: PropTypes.object.isRequired,
};

export default ProductListContainer;
