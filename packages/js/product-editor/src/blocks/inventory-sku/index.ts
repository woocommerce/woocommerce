/**
 * External dependencies
 */
import { registerWooBlockType } from '@woocommerce/block-templates';

/**
 * Internal dependencies
 */
import blockConfiguration from './block.json';
import { Edit } from './edit';

const { name, ...metadata } = blockConfiguration;

export { metadata, name };

export const settings = {
	example: {},
	edit: Edit,
};

export const init = () =>
	registerWooBlockType( {
		name,
		metadata: metadata as never,
		settings: settings as never,
	} );
