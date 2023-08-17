/**
 * External dependencies
 */
import { BlockConfiguration } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { initBlock } from '../../utils/init-block';
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
	return initBlock( { name, metadata, settings } );
}
