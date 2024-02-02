/**
 * External dependencies
 */
import type { BlockConfiguration } from '@wordpress/blocks';

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

	// Use `product-editor/entity-prop` context.
	const usesContext = [
		...( settings.usesContext, [] ),
		'product-editor/entity-prop',
	];

	return {
		...settings,
		usesContext,
	};
}
