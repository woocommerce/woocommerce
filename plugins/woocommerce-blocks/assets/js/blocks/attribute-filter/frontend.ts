/**
 * External dependencies
 */
import { renderFrontend } from '@woocommerce/base-utils';

/**
 * Internal dependencies
 */
import Block from './block';
import { blockAttributes } from './attributes';
import metadata from './block.json';

const getProps = ( el: HTMLElement ) => {
	return {
		isEditor: false,
		attributes: {
			attributeId: parseInt( el.dataset.attributeId || '0', 10 ),
			showCounts: el.dataset.showCounts === 'true',
			queryType:
				el.dataset.queryType || metadata.attributes.queryType.default,
			heading: el.dataset.heading || blockAttributes.heading.default,
			headingLevel: el.dataset.headingLevel
				? parseInt( el.dataset.headingLevel, 10 )
				: metadata.attributes.headingLevel.default,
			displayStyle:
				el.dataset.displayStyle ||
				metadata.attributes.displayStyle.default,
			showFilterButton: el.dataset.showFilterButton === 'true',
			selectType:
				el.dataset.selectType || metadata.attributes.selectType.default,
		},
	};
};

renderFrontend( {
	selector: '.wp-block-woocommerce-attribute-filter',
	Block,
	getProps,
} );
