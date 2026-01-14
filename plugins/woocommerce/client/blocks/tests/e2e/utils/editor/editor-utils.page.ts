/**
 * External dependencies
 */
import { Page } from '@playwright/test';
import {
	Editor as CoreEditor,
	expect,
} from '@wordpress/e2e-test-utils-playwright';

type EditorConstructorProps = {
	page: Page;
	wpCoreVersion: number;
};

export class Editor extends CoreEditor {
	wpCoreVersion: number;

	constructor( { page, wpCoreVersion }: EditorConstructorProps ) {
		super( { page } );
		this.wpCoreVersion = wpCoreVersion;
	}

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
	 * Clicks the global block inserter for given action.
	 *
	 * @param action - The action to perform on the global block inserter ( 'toggle' | 'open' | 'close' ).
	 * @default 'toggle'
	 */
	async clickGlobalBlockInserter(
		action: 'toggle' | 'open' | 'close' = 'toggle'
	) {
		const toggleButton = this.page.getByRole( 'button', {
			name:
				this.wpCoreVersion >= 6.8
					? 'Block Inserter'
					: 'Toggle block inserter',
			exact: true,
		} );

		const isOpen =
			( await toggleButton.getAttribute( 'aria-pressed' ) ) === 'true';

		if (
			action === 'toggle' ||
			( action === 'open' && ! isOpen ) ||
			( action === 'close' && isOpen )
		) {
			await toggleButton.click();
		}
	}

	/**
	 * Opens the global inserter.
	 */
	async openGlobalBlockInserter() {
		await this.clickGlobalBlockInserter( 'open' );
	}

	/**
	 * Closes the global inserter.
	 */
	async closeGlobalBlockInserter() {
		await this.clickGlobalBlockInserter( 'close' );
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

	/**
	 * Search for a template or template part in the Site Editor.
	 */
	async searchTemplate( { templateName }: { templateName: string } ) {
		const templateCards = this.page.locator(
			'.dataviews-view-grid > .dataviews-view-grid__card'
		);
		const templatesBeforeSearch = await templateCards.count();

		await this.page.getByPlaceholder( 'Search' ).fill( templateName );

		await expect(
			this.page.getByRole( 'button', { name: 'Reset Search' } )
		).toBeVisible();
		await expect( this.page.getByLabel( 'No results' ) ).toBeHidden();

		// Wait for the grid to update with fewer items than before.
		// Using expect.poll() for a retrying assertion since toHaveCount
		// requires an exact number.
		await expect
			.poll( () => templateCards.count(), { timeout: 5000 } )
			.toBeLessThan( templatesBeforeSearch );
	}

	/**
	 * Opens a template or template part in the Site Editor given its name.
	 */
	async openTemplate( { templateName }: { templateName: string } ) {
		const templateButton = this.page
			.getByRole( 'button', {
				name: templateName,
				exact: true,
			} )
			.first();
		if ( ! ( await templateButton.isVisible() ) ) {
			await this.searchTemplate( { templateName } );
		}

		await templateButton.click();

		// Wait until editor has loaded.
		await this.page
			.getByRole( 'heading', { name: templateName, level: 1 } )
			.waitFor();
	}

	async revertTemplate( { templateName }: { templateName: string } ) {
		await this.searchTemplate( { templateName } );

		await this.page
			.getByRole( 'button', { name: 'Actions' } )
			.first()
			.click();
		await this.page
			.getByRole( 'menuitem', { name: /Reset|Delete/ } )
			.click();

		const responsePromise = this.page.waitForResponse(
			( response ) =>
				( response.url().includes( 'wp-json/wp/v2/templates' ) ||
					response
						.url()
						.includes( 'wp-json/wp/v2/template-parts' ) ) &&
				response.status() === 200 &&
				response.request().method() === 'POST'
		);

		await this.page.getByRole( 'button', { name: /Reset|Delete/ } ).click();

		await responsePromise;

		await this.page
			.getByLabel( 'Dismiss this notice' )
			.getByText( /reset|deleted/ )
			.waitFor();
	}

	async createTemplate( { templateName }: { templateName: string } ) {
		// We need to take into account two versions of WordPress where label has changed.
		await this.page
			.getByLabel( 'Add Template' )
			.or( this.page.getByText( 'Add New Template' ) )
			.click();

		const dialog = this.page.getByRole( 'dialog' );
		await dialog.getByRole( 'button', { name: templateName } ).click();
		// There is the chance that the Add template dialog is opened before
		// product taxonomies could load. In that case, the screen to select
		// whether to create a template for a specific taxonomy or for all of
		// them won't be shown. That's why we click the 'All Categories' /
		// 'All Tags' button only if visible.
		const allButton = dialog.getByRole( 'button', {
			name: 'For all items',
		} );
		if ( await allButton.isVisible() ) {
			await allButton.click();
		}
		await this.page.getByLabel( 'Fallback content' ).click();
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
	 * attributes object and uses that data to create the block object.
	 */
	async insertBlockUsingGlobalInserter( blockTitle: string ) {
		await this.openGlobalBlockInserter();
		await this.page.getByPlaceholder( 'Search' ).fill( blockTitle );
		await this.page
			.getByRole( 'option', { name: blockTitle, exact: true } )
			.first()
			.click();
	}

	/**
	 * This is to avoid tests failing due to two notices appearing at the same
	 * time. This is an upstream issue with the `saveSiteEditorEntities` method.
	 * It should be removed once the upstream issue is fixed.
	 *
	 * @see https://github.com/WordPress/gutenberg/issues/69042
	 */
	saveSiteEditorEntities = async ( {
		isOnlyCurrentEntityDirty = false,
	}: {
		isOnlyCurrentEntityDirty?: boolean;
	} = {} ) => {
		try {
			await new CoreEditor( { page: this.page } ).saveSiteEditorEntities(
				{
					isOnlyCurrentEntityDirty,
				}
			);
		} catch ( error ) {
			if (
				! ( error instanceof Error ) ||
				! error.message.includes( 'strict mode violation' )
			) {
				throw error;
			}
		}
	};
}
