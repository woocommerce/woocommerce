/**
 * External dependencies
 */
import { BlockConfiguration } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { initBlock } from '../../utils/init-blocks';
import blockConfiguration from './block.json';
import { Edit } from './edit';
import { SalePriceBlockAttributes } from './types';

const { name, ...metadata } =
	blockConfiguration as BlockConfiguration< SalePriceBlockAttributes >;

export { metadata, name };

export const settings: Partial<
	BlockConfiguration< SalePriceBlockAttributes >
> = {
	example: {},
	edit: Edit,
};

export function init() {
	return initBlock( { name, metadata, settings } );
}
