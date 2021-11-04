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
		attributes: {
			displayStyle: el.dataset.displayStyle,
			heading: el.dataset.heading,
			headingLevel: el.dataset.headingLevel || 3,
		},
	};
};

renderFrontend( {
	selector: '.wp-block-woocommerce-active-filters',
	Block,
	getProps,
} );
