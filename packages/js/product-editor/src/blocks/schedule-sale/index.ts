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
import { ScheduleSalePricingBlockAttributes } from './types';

const { name, ...metadata } =
	blockConfiguration as BlockConfiguration< ScheduleSalePricingBlockAttributes >;

export { metadata, name };

export const settings: Partial<
	BlockConfiguration< ScheduleSalePricingBlockAttributes >
> = {
	example: {},
	edit: Edit,
};

export function init() {
	return registerWooBlockType( { name, metadata, settings } );
}
