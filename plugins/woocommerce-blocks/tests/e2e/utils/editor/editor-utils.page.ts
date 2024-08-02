/**
 * External dependencies
 */
import { Editor as CoreEditor } from '@wordpress/e2e-test-utils-playwright';

export class Editor extends CoreEditor {
	async getBlockByName( name: string ) {
		const blockSelector = `[data-type="${ name }"]`;
		const canvasLocator = this.page
			.locator( '.editor-styles-wrapper, iframe[name=editor-canvas]' )
			.first();

		const isFramed = await canvasLocator.evaluate(
			( node ) => node.tagName === 'IFRAME'
		);

		if ( isFramed ) {
			return this.canvas.locator( blockSelector );
		}

		return this.page.locator( blockSelector );
	}

	async getBlockRootClientId( clientId: string ) {
		return this.page.evaluate< string | null, string >( ( id ) => {
			return window.wp.data
				.select( 'core/block-editor' )
				.getBlockRootClientId( id );
		}, clientId );
	}

	/**
	 * Opens the global inserter.
	 */
	async openGlobalBlockInserter() {
		const toggleButton = this.page.getByRole( 'button', {
			name: 'Toggle block inserter',
		} );
		const isOpen =
			( await toggleButton.getAttribute( 'aria-pressed' ) ) === 'true';

		if ( ! isOpen ) {
			await toggleButton.click();
			await this.page.locator( '.block-editor-inserter__menu' ).waitFor();
		}
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
			await this.saveSiteEditorEntities( {
				isOnlyCurrentEntityDirty: true,
			} );
		}
	}

	async revertTemplate( { templateName }: { templateName: string } ) {
		await this.page.getByPlaceholder( 'Search' ).fill( templateName );
		await this.page.getByLabel( templateName, { exact: true } ).click();

		await this.page.getByLabel( 'Actions' ).click();
		await this.page
			.getByRole( 'menuitem', { name: /Reset|Delete/ } )
			.click();
		await this.page.getByRole( 'button', { name: /Reset|Delete/ } ).click();

		await this.page
			.getByLabel( 'Dismiss this notice' )
			.getByText( /reset|deleted/ )
			.waitFor();
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
