/**
 * External dependencies
 */
import { withRestApiHydration } from '@woocommerce/block-hocs';
import { renderFrontend } from '@woocommerce/base-utils';

/**
 * Internal dependencies
 */
import Block from './block.js';

const getProps = ( el ) => {
	return {
		attributes: {
			showCounts: el.dataset.showCounts === 'true',
			heading: el.dataset.heading,
			headingLevel: el.dataset.headingLevel || 3,
			showFilterButton: el.dataset.showFilterButton === 'true',
		},
	};
};

renderFrontend( {
	selector: '.wp-block-woocommerce-stock-filter',
	Block: withRestApiHydration( Block ),
	getProps,
} );
