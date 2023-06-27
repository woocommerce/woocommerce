/**
 * External dependencies
 */
import { Page } from '@playwright/test';
import { Editor } from '@wordpress/e2e-test-utils-playwright';
import { BlockRepresentation } from '@wordpress/e2e-test-utils-playwright/build-types/editor/insert-block';
/**
 * Internal dependencies
 */

export class EditorUtils {
	editor: Editor;
	page: Page;
	constructor( editor: Editor, page: Page ) {
		this.editor = editor;
		this.page = page;
	}

	async getBlockByName( name: string ) {
		return this.editor.canvas.locator( `[data-type="${ name }"]` );
	}

	// todo: Make a PR to @wordpress/e2e-test-utils-playwright to add this method.
	/**
	 * Inserts a block after a given client ID.
	 *
	 */
	async insertBlock(
		blockRepresentation: BlockRepresentation,
		index?: string,
		rootClientId?: string
	) {
		await this.page.evaluate(
			( {
				blockRepresentation: _blockRepresentation,
				index: _index,
				rootClientId: _rootClientId,
			} ) => {
				function recursiveCreateBlock( {
					name,
					attributes = {},
					innerBlocks = [],
				}: BlockRepresentation ): BlockRepresentation {
					return window.wp.blocks.createBlock(
						name,
						attributes,
						innerBlocks.map( ( innerBlock ) =>
							recursiveCreateBlock( innerBlock )
						)
					);
				}
				const block = recursiveCreateBlock( _blockRepresentation );

				window.wp.data
					.dispatch( 'core/block-editor' )
					.insertBlock( block, _index, _rootClientId );
			},
			{ blockRepresentation, index, rootClientId }
		);
	}

	async getBlockRootClientId( clientId: string ) {
		return this.page.evaluate< string | null, string >( ( id ) => {
			return window.wp.data
				.select( 'core/block-editor' )
				.getBlockRootClientId( id );
		}, clientId );
	}
}
