/**
 * Internal dependencies
 */
import blockConfiguration from './block.json';
import anotherBlockConfiguration from './another-block.json';
import { Edit } from './edit';
import { registerProductEditorBlockType } from '../../../utils';

const { name, ...metadata } = blockConfiguration;
const { name: anotherName, ...anotherBlockMetadata } = anotherBlockConfiguration;

export { metadata, name };

export const settings = {
	example: {},
	edit: Edit,
};

export const anotherSettings = {
	example: {},
	edit: Edit,
};

export const init = () => {
	registerProductEditorBlockType( {
		name,
		metadata: metadata as never,
		settings: settings as never,
	} );
	registerProductEditorBlockType( {
		name: anotherName,
		metadata: anotherBlockMetadata as never,
		settings: anotherSettings as never,
	} );
};
