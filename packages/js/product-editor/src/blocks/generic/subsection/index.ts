/**
 * Internal dependencies
 */
import { registerProductEditorBlockType } from '../../../utils';

/**
 * Internal dependencies
 */
import blockConfiguration from './block.json';
import { SubsectionBlockEdit } from './edit';

const { name, ...metadata } = blockConfiguration;

export { metadata, name };

export const settings = {
	example: {},
	edit: SubsectionBlockEdit,
};

export function init() {
	return registerProductEditorBlockType( {
		name,
		metadata: metadata as never,
		settings: settings as never,
	} );
}
