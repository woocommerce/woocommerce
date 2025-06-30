/**
 * External dependencies
 */
import type { Block } from '@wordpress/blocks';
import { addFilter } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { CORE_NAME as PRODUCT_TITLE_ID } from './elements/product-title';
import { CORE_NAME as PRODUCT_SUMMARY_ID } from './elements/product-summary';

const EXTENDED_CORE_ELEMENTS = [ PRODUCT_SUMMARY_ID, PRODUCT_TITLE_ID ];

function registerProductCollectionElementsNamespace(
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
	registerProductCollectionElementsNamespace
);
