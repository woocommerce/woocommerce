/**
 * External dependencies
 */
import { forEach } from 'lodash';
import { render } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Block from './block.js';

const containers = document.querySelectorAll(
	'.wp-block-woocommerce-product-categories'
);

if ( containers.length ) {
	forEach( containers, ( el ) => {
		const data = JSON.parse( JSON.stringify( el.dataset ) );
		const attributes = {
			hasCount: data.hasCount === 'true',
			hasEmpty: data.hasEmpty === 'true',
			isDropdown: data.isDropdown === 'true',
			isHierarchical: data.isHierarchical === 'true',
		};
		el.classList.remove( 'is-loading' );

		render( <Block attributes={ attributes } />, el );
	} );
}
