/**
 * Internal dependencies
 */
import { registerWooBlock } from '../../utils';
import metadata from './block.json';
import { Edit } from './edit';
import { Edit as ListEdit } from './list/edit';

const { name } = metadata;

export { metadata, name };

export const settings = {
	example: {},
	edit: {
		list: ListEdit,
		default: Edit
	},
};

export const init = () =>
	registerWooBlock( {
		name,
		metadata: metadata as never,
		settings,
	} );
