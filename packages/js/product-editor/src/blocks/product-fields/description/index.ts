/**
 * External dependencies
 */
import { postContent } from '@wordpress/icons';
import { addFilter } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import blockConfiguration from './block.json';
import { DescriptionBlockEdit } from './edit';
import { registerProductEditorBlockType } from '../../../utils';
import wooDescriptionBlockWithFullEditorButton from './components/with-full-editor-toolbar-button';

const { name, ...metadata } = blockConfiguration;

export { metadata, name };

export const settings = {
	example: {},
	edit: DescriptionBlockEdit,
	icon: postContent,
};

export const init = () =>
	registerProductEditorBlockType( {
		name,
		metadata: metadata as never,
		settings: settings as never,
	} );

addFilter(
	'editor.BlockEdit',
	'woocommerce/summary-block-with-full-editor-button',
	wooDescriptionBlockWithFullEditorButton
);
