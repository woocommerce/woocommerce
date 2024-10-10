/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { ProductPriceBlockSettings } from './settings';

// @ts-expect-error: `metadata` currently does not have a type definition in WordPress core.
registerBlockType( 'woocommerce/product-price', ProductPriceBlockSettings );
