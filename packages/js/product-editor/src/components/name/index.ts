/**
 * Internal dependencies
 */
import initBlock from '../../utils/init-block';
import metadata from './block';
import { Edit } from './edit';

const { name } = metadata;

export { metadata, name };

export const settings = {
	example: {},
	edit: Edit,
};

// @ts-ignore Context schema is incorrect.
export const init = () => initBlock( { name, metadata, settings } );
