/**
 * External dependencies
 */
import { Editor as CoreEditor } from '@wordpress/e2e-test-utils-playwright';
import { BlockRepresentation } from '@wordpress/e2e-test-utils-playwright/build-types/editor/insert-block';

export class Editor extends CoreEditor {
	/**
	 * Check to see if there are any errors in the editor.
	 */
	async ensureNoErrorsOnBlockPage() {
		const errorMessages = [
			/This block contains unexpected or invalid content/gi,
			/Your site doesn’t include support for/gi,
			/There was an error whilst rendering/gi,
			/This block has encountered an error and cannot be previewed/gi,
		];

		for ( const error of errorMessages ) {
			if ( ( await this.canvas.getByText( error ).count() ) > 0 ) {
				return false;
			}
		}
		return true;
	}

	async getBlockByName( name: string ) {
		const blockSelector = `[data-type="${ name }"]`;
		const canvasLocator = this.page.locator(
			'.wp-block-post-content, iframe[name=editor-canvas]'
		);

		const isFramed = await canvasLocator.evaluate(
			( node ) => node.tagName === 'IFRAME'
		);

		if ( isFramed ) {
			return this.canvas.locator( blockSelector );
		}

		return this.page.locator( blockSelector );
	}

	async getBlockByTypeWithParent( name: string, parentName: string ) {
		const parentBlock = await this.getBlockByName( parentName );
		if ( ! parentBlock ) {
			throw new Error( `Parent block "${ parentName }" not found.` );
		}
		const block = parentBlock.locator( `[data-type="${ name }"]` );
		return block;
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
		await this.page
			.getByRole( 'button', { name: 'Toggle block inserter' } )
			.click();
	}

	/**
	 * Checks if the global inserter is open.
	 *
	 * @return {Promise<boolean>} Whether the inserter is open or not.
	 */
	async isGlobalInserterOpen() {
		const button = this.page.getByRole( 'button', {
			name: 'Toggle block inserter',
		} );

		return ( await button.getAttribute( 'aria-pressed' ) ) === 'true';
	}

	/**
	 * Opens the global inserter.
	 */
	async openGlobalBlockInserter() {
		if ( ! ( await this.isGlobalInserterOpen() ) ) {
			await this.toggleGlobalBlockInserter();
			await this.page.locator( '.block-editor-inserter__menu' ).waitFor();
		}
	}

	async enterEditMode() {
		await this.page
			.getByRole( 'button', {
				name: 'Edit',
				exact: true,
			} )
			.dispatchEvent( 'click' );

		const sidebar = this.page.locator( '.edit-site-layout__sidebar' );
		const canvasLoader = this.page.locator( '.edit-site-canvas-loader' );

		await sidebar.waitFor( {
			state: 'hidden',
		} );

		await canvasLoader.waitFor( {
			state: 'hidden',
		} );
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

	async setLayoutOption(
		option:
			| 'Align Top'
			| 'Align Bottom'
			| 'Align Middle'
			| 'Stretch to Fill'
	) {
		const button = this.page.locator(
			"button[aria-label='Change vertical alignment']"
		);

		await button.click();

		await this.page.getByText( option ).click();
	}

	async setAlignOption(
		option: 'Align Left' | 'Align Center' | 'Align Right' | 'None'
	) {
		const button = this.page.locator( "button[aria-label='Align']" );

		await button.click();

		await this.page.getByText( option ).click();
	}

	async transformIntoBlocks() {
		// Select the block, so the button is visible.
		const block = this.canvas
			.locator( `[data-type="woocommerce/legacy-template"]` )
			.first();

		if ( ! ( await block.isVisible() ) ) {
			return;
		}

		await this.selectBlocks( block );

		const transformButton = block.getByRole( 'button', {
			name: 'Transform into blocks',
		} );

		if ( transformButton ) {
			await transformButton.click();

			// save changes
			await this.saveSiteEditorEntities();
		}
	}

	async publishAndVisitPost() {
		const postId = await this.publishPost();
		await this.page.goto( `/?p=${ postId }` );
	}

	/**
	 * Unlike the `insertBlock` method, which manipulates the block tree
	 * directly, this method simulates real user behavior when inserting a
	 * block to the editor by searching for block name then clicking on the
	 * first matching result.
	 *
	 * Besides, some blocks that manipulate their attributes after insertion
	 * aren't work probably with `insertBlock` as that method requires
	 * attributes object and uses that data to creat the block object.
	 */
	async insertBlockUsingGlobalInserter( blockTitle: string ) {
		await this.openGlobalBlockInserter();
		await this.page.getByPlaceholder( 'Search' ).fill( blockTitle );
		await this.page
			.getByRole( 'option', { name: blockTitle, exact: true } )
			.first()
			.click();
	}
}
