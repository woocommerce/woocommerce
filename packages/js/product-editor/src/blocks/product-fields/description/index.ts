/**
 * Internal dependencies
 */
import blockConfiguration from './block.json';
import { DescriptionBlockEdit } from './edit';
import { registerProductEditorBlockType } from '../../../utils';

const { name, ...metadata } = blockConfiguration;

export { metadata, name };

export const settings = {
	example: {},
	edit: DescriptionBlockEdit,
};

export const init = () =>
	registerProductEditorBlockType( {
		name,
		metadata: metadata as never,
		settings: settings as never,
	} );
