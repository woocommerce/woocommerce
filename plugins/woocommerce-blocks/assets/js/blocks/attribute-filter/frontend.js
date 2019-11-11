/**
 * Internal dependencies
 */
import Block from './block.js';
import renderFrontend from '../../utils/render-frontend.js';

const getProps = ( el ) => {
	return {
		attributes: {
			attributeId: parseInt( el.dataset.attributeId || 0, 10 ),
			showCounts: el.dataset.showCounts === 'true',
			queryType: el.dataset.queryType,
		},
	};
};

renderFrontend( '.wp-block-woocommerce-attribute-filter', Block, getProps );
