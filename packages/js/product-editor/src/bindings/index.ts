/**
 * External dependencies
 */
import { store as blockEditorStore } from '@wordpress/block-editor';
import { dispatch } from '@wordpress/data';
import { addFilter } from '@wordpress/hooks';
/**
 * Internal dependencies
 */
import productEntitySource from './product-entity-source';
import extendBlockWithBoundAttributes from './extend-blocks-with-bound-attributes';

const registerBlockBindingsSource =
	// @ts-expect-error There are no types for this.
	dispatch( blockEditorStore )?.registerBlockBindingsSource;

/**
 * Check if the block binding API is available.
 * Todo: polish the conditions to check if the API is available.
 *
 * @return {boolean} Whether the block binding API is available.
 */
export function isBlockBindingAPIAvailable() {
	const isAvailable = Boolean( registerBlockBindingsSource );
	if ( ! isAvailable ) {
		console.warn( 'Binding API not available' ); // eslint-disable-line no-console
	}
	return isAvailable;
}

export default function registerCoreParagraphBindingSource() {
	if ( ! isBlockBindingAPIAvailable() ) {
		console.warn( 'Block binding API not available' ); // eslint-disable-line no-console

		/*
		 * Fallback to extending blocks with bound attributes.
		 */
		addFilter(
			'blocks.registerBlockType',
			'woocommerce/product-editor/block-edit-with-binding-attributes',
			extendBlockWithBoundAttributes
		);

		return;
	}

	// Register the (core) block bindings source.
	registerBlockBindingsSource( productEntitySource );
}
