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

const { name, ...metadata } = blockConfiguration as BlockConfiguration;

export { metadata, name };

export const settings = {
	example: {},
	edit: Edit,
};

export const init = () => registerWooBlockType( { name, metadata, settings } );
