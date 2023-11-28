/**
 * External dependencies
 */
import { box } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { registerProductEditorBlockType } from '../../../utils';
import blockConfiguration from './block.json';
import { SectionBlockEdit } from './edit';

const { name, ...metadata } = blockConfiguration;

export { metadata, name };

export const settings = {
	example: {},
	edit: SectionBlockEdit,
	icon: box,
};

export function init() {
	return registerProductEditorBlockType( {
		name,
		metadata: metadata as never,
		settings: settings as never,
	} );
}
