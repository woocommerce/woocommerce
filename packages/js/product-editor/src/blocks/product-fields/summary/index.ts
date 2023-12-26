/**
 * Internal dependencies
 */
import blockConfiguration from './block.json';
import { SummaryBlockEdit } from './edit';
import { registerProductEditorBlockType } from '../../../utils';

const { name, ...metadata } = blockConfiguration;

export { name, metadata };

export const settings = {
	example: {},
	edit: SummaryBlockEdit,
};

export function init() {
	return registerProductEditorBlockType( {
		name,
		metadata: metadata as never,
		settings: settings as never,
	} );
}
