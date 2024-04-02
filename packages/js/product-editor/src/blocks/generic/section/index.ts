/**
 * Internal dependencies
 */
import { registerProductEditorBlockType } from '../../../utils';

/**
 * Internal dependencies
 */
import blockConfiguration from './block.json';
import { SectionBlockEdit } from './edit';
import Save from './save';
import { View } from './view';

const { name, ...metadata } = blockConfiguration;

export { metadata, name };

export const settings = {
	example: {},
	edit: SectionBlockEdit,
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
