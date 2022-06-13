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
			showCounts: el.dataset.showCounts === 'true',
			heading: el.dataset.heading || blockAttributes.heading.default,
			headingLevel: el.dataset.headingLevel
				? parseInt( el.dataset.headingLevel, 10 )
				: metadata.attributes.headingLevel.default,
			showFilterButton: el.dataset.showFilterButton === 'true',
		},
		isEditor: false,
	};
};

renderFrontend( {
	selector: '.wp-block-woocommerce-stock-filter',
	Block,
	getProps,
} );
