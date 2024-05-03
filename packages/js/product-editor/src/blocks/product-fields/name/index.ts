/**
 * Internal dependencies
 */
import metadata from './block.json';
import { Edit } from './edit';
import { registerProductEditorBlockType } from '../../../utils';

const { name } = metadata;

export { metadata, name };

export const settings = {
	example: {},
	edit: Edit,
};

export const init = () =>
	registerProductEditorBlockType( {
		name,
		metadata: metadata as never,
		settings: settings as never,
	} );
