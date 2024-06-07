/**
 * Internal dependencies
 */
import { registerProductEditorBlockType } from '../../../utils';

/**
 * Internal dependencies
 */
import blockConfiguration from './block.json';
import { ProductDetailsSectionDescriptionBlockEdit } from './edit';

const { name, ...metadata } = blockConfiguration;

export { metadata, name };

export const settings = {
	example: {},
	edit: ProductDetailsSectionDescriptionBlockEdit,
};

export function init() {
	return registerProductEditorBlockType( {
		name,
		metadata: metadata as never,
		settings: settings as never,
	} );
}
