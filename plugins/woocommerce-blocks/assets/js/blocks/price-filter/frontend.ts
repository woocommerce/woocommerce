/**
 * External dependencies
 */
import { renderFrontend } from '@woocommerce/base-utils';

/**
 * Internal dependencies
 */
import Block from './block';
import metadata from './block.json';
import { blockAttributes } from './attributes';

const getProps = ( el: HTMLElement ) => {
	return {
		attributes: {
			showInputFields: el.dataset.showinputfields === 'true',
			showFilterButton: el.dataset.showfilterbutton === 'true',
			heading: el.dataset.heading || blockAttributes.heading.default,
			headingLevel: el.dataset.headingLevel
				? parseInt( el.dataset.headingLevel, 10 )
				: metadata.attributes.headingLevel.default,
		},
		isEditor: false,
	};
};

renderFrontend( {
	selector: '.wp-block-woocommerce-price-filter',
	Block,
	getProps,
} );
