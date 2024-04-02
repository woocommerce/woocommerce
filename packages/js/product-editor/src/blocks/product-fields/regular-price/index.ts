/**
 * Internal dependencies
 */
import blockConfiguration from './block.json';
import { Edit } from './edit';
import { Save } from './save';
import { View } from './view';
import { registerProductEditorBlockType } from '../../../utils';

const { name, ...metadata } = blockConfiguration;

export { metadata, name };

export const settings = {
	example: {},
	edit: Edit,
	save: Save,
	view: View,
};

export function init() {
	return registerProductEditorBlockType( {
		name,
		metadata: metadata as never,
		settings: settings as never,
	} );
}
