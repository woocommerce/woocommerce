/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { BlockConfiguration } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { registerWooBlockType } from '../../utils';
import blockConfiguration from './block.json';
import { Edit } from './edit';
import { InventoryEmailBlockAttributes } from './types';

const { name, ...metadata } =
	blockConfiguration as BlockConfiguration< InventoryEmailBlockAttributes >;

export { metadata, name };

export const settings: Partial<
	BlockConfiguration< InventoryEmailBlockAttributes >
> = {
	example: {},
	edit: Edit,
};

export function init() {
	return registerWooBlockType( { name, metadata, settings } );
}
