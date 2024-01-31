/**
 * External dependencies
 */
import { postContent } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import blockConfiguration from './block.json';
import { TextAreaBlockEdit } from './edit';
import { registerProductEditorBlockType } from '../../../utils';

const { name, ...metadata } = blockConfiguration;

export { metadata, name };

export const settings = {
	example: {},
	edit: TextAreaBlockEdit,
	icon: postContent,
};

export const init = () =>
	registerProductEditorBlockType( {
		name,
		metadata: metadata as never,
		settings: settings as never,
	} );
