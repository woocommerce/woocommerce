/**
 * External dependencies
 */
import { renderFrontend } from '@woocommerce/base-utils';

/**
 * Internal dependencies
 */
import Block from './block.js';

const getProps = ( el ) => {
	return {
		isEditor: false,
		attributes: {
			attributeId: parseInt( el.dataset.attributeId || 0, 10 ),
			showCounts: el.dataset.showCounts === 'true',
			queryType: el.dataset.queryType,
			heading: el.dataset.heading,
			headingLevel: el.dataset.headingLevel || 3,
			displayStyle: el.dataset.displayStyle,
			showFilterButton: el.dataset.showFilterButton === 'true',
		},
	};
};

renderFrontend( {
	selector: '.wp-block-woocommerce-attribute-filter',
	Block,
	getProps,
} );
