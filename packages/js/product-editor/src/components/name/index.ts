/**
 * Internal dependencies
 */
import { initBlock } from '../../utils';
import metadata from './block';
import { Edit } from './edit';

const { name } = metadata;

export { metadata, name };

export const settings = {
	example: {},
	edit: Edit,
};

export const init = () => initBlock( { name, metadata, settings } );
