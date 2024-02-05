/**
 * External dependencies
 */
import { store as blockEditorStore } from '@wordpress/block-editor';
import { dispatch } from '@wordpress/data';
/**
 * Internal dependencies
 */
import productMeta from './product-entity';

const {
	// @ts-expect-error There are no types for this.
	registerBlockBindingsSource,
} = dispatch( blockEditorStore );

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
		return;
	}
	registerBlockBindingsSource( productMeta );
}
