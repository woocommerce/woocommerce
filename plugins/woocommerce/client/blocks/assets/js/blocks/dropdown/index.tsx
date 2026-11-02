/**
 * External dependencies
 */
import { productFilterOptions } from '@woocommerce/icons';
import { registerBlockType } from '@wordpress/blocks';
import type { BlockAttributes, BlockConfiguration } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import Edit from './edit';

registerBlockType( metadata as BlockConfiguration< BlockAttributes >, {
	edit: Edit,
	icon: productFilterOptions,
	save: () => null,
} );
