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

export default function registerCoreParagraphBindingSource() {
	if ( ! registerBlockBindingsSource ) {
		console.warn( 'Binding API not available' ); // eslint-disable-line no-console
		return;
	}
	registerBlockBindingsSource( productMeta );
}
