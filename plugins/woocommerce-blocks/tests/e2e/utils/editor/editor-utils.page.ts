/**
 * External dependencies
 */
import { Page } from '@playwright/test';
import { Editor } from '@wordpress/e2e-test-utils-playwright';
import { BlockRepresentation } from '@wordpress/e2e-test-utils-playwright/build-types/editor/insert-block';

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

	async getBlockByTypeWithParent( name: string, parentName: string ) {
		const parentBlock = await this.getBlockByName( parentName );
		if ( ! parentBlock ) {
			throw new Error( `Parent block "${ parentName }" not found.` );
		}
		const block = parentBlock.locator( `[data-type="${ name }"]` );
		return block;
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

	async replaceBlockByBlockName( name: string, nameToInsert: string ) {
		await this.page.evaluate(
			( { name: _name, nameToInsert: _nameToInsert } ) => {
				const blocks = window.wp.data
					.select( 'core/block-editor' )
					.getBlocks();
				const firstMatchingBlock = blocks
					.flatMap(
						( {
							innerBlocks,
						}: {
							innerBlocks: BlockRepresentation[];
						} ) => innerBlocks
					)
					.find(
						( block: BlockRepresentation ) => block.name === _name
					);
				const { clientId } = firstMatchingBlock;
				const block = window.wp.blocks.createBlock( _nameToInsert );
				window.wp.data
					.dispatch( 'core/block-editor' )
					.replaceBlock( clientId, block );
			},
			{ name, nameToInsert }
		);
	}

	async getBlockRootClientId( clientId: string ) {
		return this.page.evaluate< string | null, string >( ( id ) => {
			return window.wp.data
				.select( 'core/block-editor' )
				.getBlockRootClientId( id );
		}, clientId );
	}

	/**
	 * Toggles the global inserter.
	 */
	async toggleGlobalBlockInserter() {
		// "Add block" selector is required to make sure performance comparison
		// doesn't fail on older branches where we still had "Add block" as label.
		await this.page.click(
			'.edit-post-header [aria-label="Add block"],' +
				'.edit-site-header [aria-label="Add block"],' +
				'.edit-post-header [aria-label="Toggle block inserter"],' +
				'.edit-site-header [aria-label="Toggle block inserter"],' +
				'.edit-widgets-header [aria-label="Add block"],' +
				'.edit-widgets-header [aria-label="Toggle block inserter"],' +
				'.edit-site-header-edit-mode__inserter-toggle'
		);
	}

	/**
	 * Checks if the global inserter is open.
	 *
	 * @return {Promise<boolean>} Whether the inserter is open or not.
	 */
	async isGlobalInserterOpen() {
		return await this.page.evaluate( () => {
			// "Add block" selector is required to make sure performance comparison
			// doesn't fail on older branches where we still had "Add block" as
			// label.
			return !! document.querySelector(
				'.edit-post-header [aria-label="Add block"].is-pressed,' +
					'.edit-site-header-edit-mode [aria-label="Add block"].is-pressed,' +
					'.edit-post-header [aria-label="Toggle block inserter"].is-pressed,' +
					'.edit-site-header [aria-label="Toggle block inserter"].is-pressed,' +
					'.edit-widgets-header [aria-label="Toggle block inserter"].is-pressed,' +
					'.edit-widgets-header [aria-label="Add block"].is-pressed,' +
					'.edit-site-header-edit-mode__inserter-toggle.is-pressed'
			);
		} );
	}

	/**
	 * Opens the global inserter.
	 */
	async openGlobalBlockInserter() {
		if ( ! ( await this.isGlobalInserterOpen() ) ) {
			await this.toggleGlobalBlockInserter();
			await this.page.waitForSelector( '.block-editor-inserter__menu' );
		}
	}

	async enterEditMode() {
		await this.editor.page
			.getByRole( 'button', {
				name: 'Edit',
				exact: true,
			} )
			.click();
	}

	async isBlockEarlierThan< T >(
		containerBlock: T,
		firstBlock: string,
		secondBlock: string
	) {
		const container =
			containerBlock instanceof Function
				? await containerBlock()
				: containerBlock;

		if ( ! container ) {
			throw new Error( 'Container block not found.' );
		}

		const childBlocks = container.locator( ':scope > .wp-block' );

		let firstBlockIndex = -1;
		let secondBlockIndex = -1;

		for ( let i = 0; i < ( await childBlocks.count() ); i++ ) {
			const blockName = await childBlocks
				.nth( i )
				.getAttribute( 'data-type' );

			if ( blockName === firstBlock ) {
				firstBlockIndex = i;
			}

			if ( blockName === secondBlock ) {
				secondBlockIndex = i;
			}

			if ( firstBlockIndex !== -1 && secondBlockIndex !== -1 ) {
				break;
			}
		}

		if ( firstBlockIndex === -1 || secondBlockIndex === -1 ) {
			throw new Error( 'Both blocks must exist within the editor' );
		}

		return firstBlockIndex < secondBlockIndex;
	}

	async waitForSiteEditorFinishLoading() {
		await this.page
			.frameLocator( 'iframe[title="Editor canvas"i]' )
			.locator( 'body > *' )
			.first()
			.waitFor();
		await this.page
			.locator( '.edit-site-canvas-spinner' )
			.waitFor( { state: 'hidden' } );
	}
}
