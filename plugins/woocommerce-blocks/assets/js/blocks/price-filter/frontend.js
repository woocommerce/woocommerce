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
			showInputFields: el.dataset.showinputfields === 'true',
			showFilterButton: el.dataset.showfilterbutton === 'true',
		},
	};
};

renderFrontend( {
	selector: '.wp-block-woocommerce-price-filter',
	Block: withRestApiHydration( Block ),
	getProps,
} );
