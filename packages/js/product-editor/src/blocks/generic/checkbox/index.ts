/**
 * Internal dependencies
 */
import blockConfiguration from './block.json';
import { Edit } from './edit';
import { Save } from './save';
import { registerProductEditorBlockType } from '../../../utils';

const { name, ...metadata } = blockConfiguration;

export { metadata, name };

export const settings = {
	example: {},
	edit: Edit,
	save: Save,
};

export const init = () =>
	registerProductEditorBlockType( {
		name,
		metadata: metadata as never,
		settings: settings as never,
	} );
