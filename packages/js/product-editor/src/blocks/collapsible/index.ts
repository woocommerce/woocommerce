/**
 * Internal dependencies
 */
import { registerWooBlockType } from '../../utils';
import metadata from './block.json';
import { Edit } from './edit';

const { name } = metadata;

export { metadata, name };

export const settings = {
	example: {},
	edit: Edit,
};

export const init = () =>
	registerWooBlockType( { name, metadata: metadata as never, settings } );
