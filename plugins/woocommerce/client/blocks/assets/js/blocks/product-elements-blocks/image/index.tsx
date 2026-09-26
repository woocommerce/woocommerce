/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import deprecated from './deprecated';
import edit from './edit';
import { BLOCK_ICON as icon } from './constants';
import metadata from './block.json';

registerBlockType( metadata, {
	deprecated,
	icon,
	edit,
	// The `woocommerce/product-sale-badge` inner block needs to persist —
	// returning `null` would drop it from the saved content.
	save: () => <InnerBlocks.Content />,
} );
