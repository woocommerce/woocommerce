/**
 * External dependencies
 */
import type { Block } from '@wordpress/blocks';
import { addFilter } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { CORE_NAME as PRODUCT_TEMPLATE_ID } from './variations/elements/product-template';
import './inspector-controls';
import './style.scss';
import './variations/product-query';
import './variations/related-products';

function registerProductQueryElementsNamespace(
	props: Block,
	blockName: string
) {
	if ( PRODUCT_TEMPLATE_ID === blockName ) {
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
