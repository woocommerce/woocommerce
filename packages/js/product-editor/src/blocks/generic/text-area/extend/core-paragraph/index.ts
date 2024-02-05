/**
 * External dependencies
 */
import type { BlockConfiguration } from '@wordpress/blocks';
/**
 * Internal dependencies
 */
import { isBlockBindingAPIAvailable } from '../../../../../bindings';
import connectBlockWithEntityProp from './components/connect-block-with-entity-prop';
import coreBlockEditWithTextareaToolbar from './components/core-block-with-text-area-toolbar';
import updateEntityPropFromBlockAttribute from './components/update-entity-prop-from-block-attribute';

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

	/*
	 * Use `product-editor/entity-prop` context.
	 * This is part of a temporary fallback
	 * until the block binding API is available.
	 * Todo: remove this when the block binding API is available
	 * (remove-when-block-binding-api-available).
	 */
	const usesContext = settings.usesContext
		? [ ...settings.usesContext, 'product-editor/entity-prop' ]
		: [];

	let edit = settings.edit;
	// Extend edit component with text area toolbar.
	if ( edit ) {
		edit = coreBlockEditWithTextareaToolbar( edit );
	}

	if ( edit ) {
		/*
		 * Connect the paragraph block with entity prop
		 * when the block binding API is not available.
		 * (remove-when-block-binding-api-available).
		 */
		if ( ! isBlockBindingAPIAvailable() ) {
			// eslint-disable-next-line no-console
			console.warn(
				'Block binding API not available. Connect the paragraph block with entity prop.'
			);
			edit = connectBlockWithEntityProp( edit );
		} else {
			/*
			 * Even when the block binding API is available,
			 * the update data function handling is not available.
			 * Let's handle the update data function here.
			 * @see https://github.com/WordPress/gutenberg/blob/f38eb429b8ba5153c50fabad3367f94c3289746d/packages/block-editor/src/hooks/use-bindings-attributes.js#L51
			 */
			edit = updateEntityPropFromBlockAttribute( edit );
		}
	}

	return {
		...settings,
		usesContext,
		edit,
	};
}
