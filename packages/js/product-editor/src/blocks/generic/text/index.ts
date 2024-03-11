/**
 * External dependencies
 */
import { BlockConfiguration } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { registerProductEditorBlockType } from '../../../utils';

/**
 * Internal dependencies
 */
import blockConfiguration from './block.json';
import { TextFieldBlockEdit } from './edit';
import { TextBlockAttributes } from './types';

const { name, ...metadata } =
	blockConfiguration as BlockConfiguration< TextBlockAttributes >;

export { metadata, name };

export const settings = {
	example: {},
	edit: TextFieldBlockEdit,
};

export const init = () =>
	registerProductEditorBlockType( {
		name,
		metadata: metadata as never,
		settings: settings as never,
	} );
