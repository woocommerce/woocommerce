/**
 * External dependencies
 */
import type { BlockConfiguration } from '@wordpress/blocks';
/**
 * Internal dependencies
 */
import coreBlockEditWithTextareaToolbar from './components/core-block-with-text-area-toolbar';

/**
 * Extend core/paragraph block with attributes
 * to
 *
 * @param {BlockConfiguration} settings - The block settings.
 * @param {string}             name     - The block name.
 * @return {BlockConfiguration} The updated block settings.
 */
export default function blockChildOfTextAreaField(
	settings: BlockConfiguration,
	name: string
): BlockConfiguration {
	if ( name !== 'core/paragraph' ) {
		return settings;
	}

	let edit = settings.edit;
	// Extend edit component with text area toolbar.
	if ( edit ) {
		edit = coreBlockEditWithTextareaToolbar( edit );
	}

	return {
		...settings,
		edit,
	};
}
