/**
 * External dependencies
 */
import { Block } from '@wordpress/blocks';
import { addFilter } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import './inspector-controls';
import './variations/product-query';
import './variations/products-on-sale';

function registerProductQueryVariationAttributes(
	props: Block,
	blockName: string
) {
	if ( blockName === 'core/query' ) {
		// Gracefully handle if settings.attributes is undefined.
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore -- We need this because `attributes` is marked as `readonly`
		props.attributes = {
			...props.attributes,
			__woocommerceVariationProps: {
				type: 'object',
			},
		};
	}
	return props;
}

addFilter(
	'blocks.registerBlockType',
	'core/custom-class-name/attribute',
	registerProductQueryVariationAttributes
);
