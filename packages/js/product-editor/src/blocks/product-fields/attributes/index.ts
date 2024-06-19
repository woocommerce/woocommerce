/**
 * Internal dependencies
 */
import metadata from './block.json';
import { AttributesBlockEdit } from './edit';
import { registerProductEditorBlockType } from '../../../utils';

const { name } = metadata;

export { metadata, name };

export const settings = {
	example: {},
	edit: AttributesBlockEdit,
};

export const init = () =>
	registerProductEditorBlockType( {
		name,
		metadata: metadata as never,
		settings: settings as never,
	} );
