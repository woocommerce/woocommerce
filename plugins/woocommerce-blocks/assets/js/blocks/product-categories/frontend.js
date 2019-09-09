/**
 * Internal dependencies
 */
import Block from './block.js';
import getCategories from './get-categories';
import renderFrontend from '../../utils/render-frontend.js';

const getProps = ( el ) => {
	const attributes = {
		hasCount: el.dataset.hasCount === 'true',
		hasEmpty: el.dataset.hasEmpty === 'true',
		isDropdown: el.dataset.isDropdown === 'true',
		isHierarchical: el.dataset.isHierarchical === 'true',
	};

	return {
		attributes,
		categories: getCategories( attributes ),
	};
};

renderFrontend( '.wp-block-woocommerce-product-categories', Block, getProps );
