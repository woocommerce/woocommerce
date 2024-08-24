/**
 * External dependencies
 */
import {
	BlockAttributes,
	InnerBlockTemplate,
	registerBlockVariation,
} from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { registerBlockSingleProductTemplate } from '@woocommerce/atomic-utils';

/**
 * Internal dependencies
 */
import { QUERY_LOOP_ID } from '../constants';
import { RELATED_PRODUCTS_VARIATION_NAME, RelatedProductsControlsBlockVariationSettings } from './related-products-settings';


registerBlockVariation(
	QUERY_LOOP_ID,
	// @ts-expect-error: `settings` currently does not have a correct type definition in WordPress core.
	RelatedProductsControlsBlockVariationSettings
);
