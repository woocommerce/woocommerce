/**
 * Internal dependencies
 */
import initBlock from '../../utils/init-block';
import metadata from './block';
import { Edit } from './edit';

const { name } = metadata;

export { metadata, name };

export const settings = {
	edit: Edit,
};

export const init = () => initBlock( { name, metadata, settings } );
