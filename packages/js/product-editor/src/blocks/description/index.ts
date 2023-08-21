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

const { name, ...metadata } = blockConfiguration as BlockConfiguration;

export { metadata, name };

export const settings = {
	example: {},
	edit: Edit,
};

export const init = () => initBlock( { name, metadata, settings } );
