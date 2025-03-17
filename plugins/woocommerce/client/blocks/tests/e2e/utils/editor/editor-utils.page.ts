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

		// Depending on the context, we need to click either on a link (in the template page)
		// or a button (in the template-parts/patterns page) to visit the template.
		const link = this.page.getByRole( 'link', {
			name: templateName,
			exact: true,
		} );

		if ( await link.isVisible() ) {
			await link.click();
		}

		const button = this.page
			.getByRole( 'button', {
				name: new RegExp( templateName, 'i' ),
				exact: true,
			} )
			.and( this.page.locator( '.is-link' ) );

		if ( await button.isVisible() ) {
			await button.click();
		}

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
