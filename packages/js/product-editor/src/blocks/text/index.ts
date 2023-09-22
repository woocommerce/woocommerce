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
import { TextBlockAttributes } from './types';

const { name, ...metadata } =
	blockConfiguration as BlockConfiguration< TextBlockAttributes >;

export { metadata, name };

export const settings = {
	example: {},
	edit: Edit,
};

export const init = () => initBlock( { name, metadata, settings } );
