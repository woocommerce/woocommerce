/**
 * Internal dependencies
 */
import { registerProductEditorBlockType } from '../../../utils';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import { CollapsibleBlockEdit } from './edit';

const { name } = metadata;

export { metadata, name };

export const settings = {
	example: {},
	edit: CollapsibleBlockEdit,
};

export const init = () =>
	registerProductEditorBlockType( {
		name,
		metadata: metadata as never,
		settings: settings as never,
	} );
