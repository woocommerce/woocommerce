/**
 * External dependencies
 */
import { BlockConfiguration } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { registerWooBlockType } from '../../utils';
import blockConfiguration from './block.json';
import { Edit } from './edit';
import { SummaryAttributes } from './types';

const { name, ...metadata } =
	blockConfiguration as BlockConfiguration< SummaryAttributes >;

export { name, metadata };

export const settings = {
	example: {},
	edit: Edit,
};

export function init() {
	return registerWooBlockType< SummaryAttributes >( {
		name,
		metadata,
		settings,
	} );
}
