/**
 * External dependencies
 */
import { render } from 'react-dom';

/**
 * Internal dependencies
 */
import Block from './block.js';
import getCategories from './get-categories';

const containers = document.querySelectorAll(
	'.wp-block-woocommerce-product-categories'
);

if ( containers.length ) {
	Array.prototype.forEach.call( containers, ( el ) => {
		const data = JSON.parse( JSON.stringify( el.dataset ) );
		const attributes = {
			hasCount: data.hasCount === 'true',
			hasEmpty: data.hasEmpty === 'true',
			isDropdown: data.isDropdown === 'true',
			isHierarchical: data.isHierarchical === 'true',
		};
		const categories = getCategories( attributes );

		el.classList.remove( 'is-loading' );

		render( <Block attributes={ attributes } categories={ categories } />, el );
	} );
}
