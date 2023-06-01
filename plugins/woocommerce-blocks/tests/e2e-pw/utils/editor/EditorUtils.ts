/**
 * External dependencies
 */
import { Editor } from '@wordpress/e2e-test-utils-playwright';
/**
 * Internal dependencies
 */

export class EditorUtils {
	editor: Editor;
	constructor( editor: Editor ) {
		this.editor = editor;
	}

	async getBlockByName( name: string ) {
		return this.editor.canvas.locator( `[data-type="${ name }"]` );
	}
}
