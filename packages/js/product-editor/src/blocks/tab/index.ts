/**
 * External dependencies
 */
import { BlockConfiguration } from '@wordpress/blocks';
import { registerWooBlockType } from '@woocommerce/block-templates';

/**
 * Internal dependencies
 */
import { initBlock } from '../../utils/init-block';
import blockConfiguration from './block.json';
import { Edit, TabBlockAttributes } from './edit';

const { name, ...metadata } =
	blockConfiguration as BlockConfiguration< TabBlockAttributes >;

export { metadata, name };

export const settings: Partial< BlockConfiguration< TabBlockAttributes > > = {
	example: {},
	edit: Edit,
};

export function init() {
	registerWooBlockType( { name, metadata, settings } );
}
