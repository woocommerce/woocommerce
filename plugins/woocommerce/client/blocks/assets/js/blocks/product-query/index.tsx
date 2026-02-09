/**
 * External dependencies
 */
import type { Block } from '@wordpress/blocks';
import { addFilter } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { CORE_NAME as PRODUCT_TITLE_ID } from './variations/elements/product-title';
import { CORE_NAME as PRODUCT_SUMMARY_ID } from './variations/elements/product-summary';
import { CORE_NAME as PRODUCT_TEMPLATE_ID } from './variations/elements/product-template';
import './inspector-controls';
import './style.scss';
import './variations/product-query';
import './variations/related-products';

const EXTENDED_CORE_ELEMENTS = [
	PRODUCT_SUMMARY_ID,
	PRODUCT_TEMPLATE_ID,
	PRODUCT_TITLE_ID,
];

function registerProductQueryElementsNamespace(
	props: Block,
	blockName: string
) {
	if ( EXTENDED_CORE_ELEMENTS.includes( blockName ) ) {
		// Gracefully handle if settings.attributes is undefined.
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore -- We need this because `attributes` is marked as `readonly`
		props.attributes = {
			...props.attributes,
			__woocommerceNamespace: {
				type: 'string',
			},
		};
	}

	return props;
}

addFilter(
	'blocks.registerBlockType',
	'core/custom-class-name/attribute',
	registerProductQueryElementsNamespace
);
