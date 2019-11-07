/**
 * Internal dependencies
 */
import renderFrontend from '../../utils/render-frontend.js';

/**
 * Internal dependencies
 */
import Block from './block.js';

const getProps = ( el ) => {
	return {
		attributes: {
			showInputFields: el.dataset.showinputfields === 'true',
			showFilterButton: el.dataset.showfilterbutton === 'true',
		},
	};
};

renderFrontend( '.wp-block-woocommerce-price-filter', Block, getProps );
