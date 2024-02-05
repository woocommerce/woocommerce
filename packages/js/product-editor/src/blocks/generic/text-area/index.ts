/**
 * External dependencies
 */
import { postContent } from '@wordpress/icons';
import { addFilter } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import blockConfiguration from './block.json';
import { TextAreaBlockEdit } from './edit';
import { registerProductEditorBlockType } from '../../../utils';
import coreParagraphChildOfTextAreaField from './extend/core-paragraph';

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

addFilter(
	'blocks.registerBlockType',
	'woocommerce/product-text-area-field',
	coreParagraphChildOfTextAreaField
);
