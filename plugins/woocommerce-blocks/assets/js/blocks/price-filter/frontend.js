/**
 * External dependencies
 */
import { render } from 'react-dom';

/**
 * Internal dependencies
 */
import Block from './block.js';

const containers = document.querySelectorAll(
	'.wp-block-woocommerce-price-filter'
);

if ( containers.length ) {
	Array.prototype.forEach.call( containers, ( el ) => {
		const attributes = {
			showInputFields: el.dataset.showinputfields === 'true',
			showFilterButton: el.dataset.showfilterbutton === 'true',
		};
		el.classList.remove( 'is-loading' );

		render( <Block attributes={ attributes } />, el );
	} );
}
