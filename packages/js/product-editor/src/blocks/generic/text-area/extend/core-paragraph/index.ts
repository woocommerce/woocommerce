/**
 * External dependencies
 */
import type { BlockConfiguration } from '@wordpress/blocks';
/**
 * Internal dependencies
 */
import { isBlockBindingAPIAvailable } from '../../../../../bindings';
import connectCoreParagraphWithWithEntityProp from './components/connect-core-paragraph-with-entity-prop';
import coreParagraphBlockEditWithTextareaToolbar from './components/core-paragraph-with-text-area-toolbar';

/**
 * Extend core/paragraph block with attributes
 * to
 *
 * @param {BlockConfiguration} settings - The block settings.
 * @param {string}             name     - The block name.
 * @return {BlockConfiguration} The updated block settings.
 */
export default function coreParagraphChildOfTextAreaField(
	settings: BlockConfiguration,
	name: string
) {
	if ( name !== 'core/paragraph' ) {
		return settings;
	}

	/*
	 * Use `product-editor/entity-prop` context.
	 * This is part of a temporary fallback
	 * until the block binding API is available.
	 * Todo: remove this when the block binding API is available.
	 */
	const usesContext = settings.usesContext
		? [ ...settings.usesContext, 'product-editor/entity-prop' ]
		: [];

	let edit = settings.edit;
	// Extend edit component with text area toolbar.
	if ( edit ) {
		edit = coreParagraphBlockEditWithTextareaToolbar( edit );
	}

	/*
	 * Connect the paragraph block with entity prop
	 * when the block binding API is not available.
	 */
	if ( ! isBlockBindingAPIAvailable() && edit ) {
		// eslint-disable-next-line no-console
		console.warn(
			'Block binding API not available. Connect the paragraph block with entity prop.'
		);
		edit = connectCoreParagraphWithWithEntityProp( edit );
	}

	return {
		...settings,
		usesContext,
		edit,
	};
}
