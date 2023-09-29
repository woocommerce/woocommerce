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
import { RadioBlockAttributes } from './types';

const { name, ...metadata } =
	blockConfiguration as BlockConfiguration< RadioBlockAttributes >;

export { metadata, name };

export const settings: Partial< BlockConfiguration< RadioBlockAttributes > > = {
	example: {},
	edit: Edit,
};

export function init() {
	return registerWooBlockType( { name, metadata, settings } );
}
