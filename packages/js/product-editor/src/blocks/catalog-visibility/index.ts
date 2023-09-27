/**
 * External dependencies
 */
import { BlockConfiguration } from '@wordpress/blocks';
import { registerWooBlockType } from '@woocommerce/block-templates';

/**
 * Internal dependencies
 */
import blockConfiguration from './block.json';
import { Edit } from './edit';
import { CatalogVisibilityBlockAttributes } from './types';

const { name, ...metadata } =
	blockConfiguration as BlockConfiguration< CatalogVisibilityBlockAttributes >;

export { metadata, name };

export const settings: Partial<
	BlockConfiguration< CatalogVisibilityBlockAttributes >
> = {
	example: {},
	edit: Edit,
};

export function init() {
	return registerWooBlockType( { name, metadata, settings } );
}
