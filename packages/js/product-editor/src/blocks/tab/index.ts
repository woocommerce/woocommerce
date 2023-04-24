/**
 * Internal dependencies
 */
import initBlock from '../../utils/init-block';
import metadata from './block.json';
import { Edit } from './edit';

const { name } = metadata;

export { metadata, name };

export const settings = {
	example: {},
	edit: Edit,
};

export const init = () => initBlock( { name, metadata, settings } );
