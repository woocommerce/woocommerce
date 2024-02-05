/**
 * External dependencies
 */
import type { BlockConfiguration } from '@wordpress/blocks';
/**
 * Internal dependencies
 */
import coreParagraphBlockEditChildTextArea from './components/core-paragraph-with-text-area-role';
import { isBlockBindingAPIAvailable } from '../../../../../bindings';

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

	if ( isBlockBindingAPIAvailable() ) {
		// Dont extend when block binding API is available.
		return settings;
	}

	// Use `product-editor/entity-prop` context.
	const usesContext = [
		...( settings.usesContext, [] ),
		'product-editor/entity-prop',
	];

	return {
		...settings,
		usesContext,
		edit: settings.edit
			? coreParagraphBlockEditChildTextArea( settings.edit )
			: null,
	};
}
