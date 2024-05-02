/**
 * Internal dependencies
 */
import blockConfiguration from './block.json';
import variantBlockConfiguration from './variant-block.json';
import { Edit } from './edit';
import { registerProductEditorBlockType } from '../../../utils';

const { name, ...metadata } = blockConfiguration;
const { name: variantBlockName, ...variantBlockMetadata } =
	variantBlockConfiguration;

export { metadata, name };

export const settings = {
	example: {},
	edit: Edit,
};

export const variantSettings = {
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
		name: variantBlockName,
		metadata: variantBlockMetadata as never,
		settings: variantSettings as never,
	} );
};
