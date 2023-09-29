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
