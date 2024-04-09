/**
 * External dependencies
 */
import { Page } from '@playwright/test';
import { Admin, Editor } from '@wordpress/e2e-test-utils-playwright';
import { BlockRepresentation } from '@wordpress/e2e-test-utils-playwright/build-types/editor/insert-block';

/**
 * Internal dependencies
 */
import type { TemplateType } from '../../utils/types';

export class EditorUtils {
	editor: Editor;
	page: Page;
	admin: Admin;
	constructor( editor: Editor, page: Page, admin: Admin ) {
		this.editor = editor;
		this.page = page;
		this.admin = admin;
	}

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
			if ( ( await this.editor.canvas.getByText( error ).count() ) > 0 ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Checks if the editor is inside an iframe.
	 */
	private async isEditorInsideIframe() {
		try {
			return ( await this.editor.canvas.locator( '*' ).count() ) > 0;
		} catch ( e ) {
			return false;
		}
	}

	async getBlockByName( name: string ) {
		if ( await this.isEditorInsideIframe() ) {
			return this.editor.canvas.locator( `[data-type="${ name }"]` );
		}
		return this.page.locator( `[data-type="${ name }"]` );
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
	 * Inserts a block inside a given client ID.
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

	async removeBlocks( { name }: { name: string } ) {
		await this.page.evaluate(
			( { name: _name } ) => {
				const blocks = window.wp.data
					.select( 'core/block-editor' )
					.getBlocks() as ( BlockRepresentation & {
					clientId: string;
				} )[];
				const matchingBlocksClientIds = blocks
					.filter( ( block ) => {
						return block && block.name === _name;
					} )
					.map( ( block ) => block?.clientId );
				window.wp.data
					.dispatch( 'core/block-editor' )
					.removeBlocks( matchingBlocksClientIds );
			},
			{ name }
		);
	}

	async closeModalByName( name: string ) {
		const isModalOpen = await this.page.getByLabel( name ).isVisible();

		// eslint-disable-next-line playwright/no-conditional-in-test
		if ( isModalOpen ) {
			await this.page
				.getByLabel( name )
				.getByRole( 'button', { name: 'Close' } )
				.click();
		}
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
			await this.page.waitForSelector( '.block-editor-inserter__menu' );
		}
	}

	async enterEditMode() {
		await this.editor.page
			.getByRole( 'button', {
				name: 'Edit',
				exact: true,
			} )
			.dispatchEvent( 'click' );

		await this.page.locator( '.edit-site-layout__sidebar' ).waitFor( {
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

	async waitForSiteEditorFinishLoading() {
		await this.page
			.frameLocator( 'iframe[title="Editor canvas"i]' )
			.locator( 'body > *' )
			.first()
			.waitFor();
		await this.page
			.locator( '.edit-site-canvas-loader' )
			.waitFor( { state: 'hidden' } );
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

	async closeWelcomeGuideModal() {
		await this.page.waitForFunction( () => {
			return (
				window.wp &&
				window.wp.data &&
				window.wp.data.dispatch( 'core/preferences' )
			);
		} );

		// Disable the welcome guide for the site editor.
		await this.page.evaluate( () => {
			return Promise.all( [
				window.wp.data
					.dispatch( 'core/preferences' )
					.set( 'core/edit-site', 'welcomeGuide', false ),
				window.wp.data
					.dispatch( 'core/preferences' )
					.set( 'core/edit-site', 'welcomeGuideStyles', false ),
				window.wp.data
					.dispatch( 'core/preferences' )
					.set( 'core/edit-site', 'welcomeGuidePage', false ),
				window.wp.data
					.dispatch( 'core/preferences' )
					.set( 'core/edit-site', 'welcomeGuideTemplate', false ),
				window.wp.data
					.dispatch( 'core/preferences' )
					.set( 'core/edit-post', 'welcomeGuide', false ),
				window.wp.data
					.dispatch( 'core/preferences' )
					.set( 'core/edit-post', 'welcomeGuideStyles', false ),
				window.wp.data
					.dispatch( 'core/preferences' )
					.set( 'core/edit-post', 'welcomeGuidePage', false ),

				window.wp.data
					.dispatch( 'core/preferences' )
					.set( 'core/edit-post', 'welcomeGuideTemplate', false ),
			] );
		} );
	}

	async transformIntoBlocks() {
		// Select the block, so the button is visible.
		const block = this.page
			.frameLocator( 'iframe[name="editor-canvas"]' )
			.locator( `[data-type="woocommerce/legacy-template"]` )
			.first();

		if ( ! ( await block.isVisible() ) ) {
			return;
		}

		await this.editor.selectBlocks( block );

		const transformButton = block.getByRole( 'button', {
			name: 'Transform into blocks',
		} );

		if ( transformButton ) {
			await transformButton.click();

			// save changes
			await this.saveSiteEditorEntities();
		}
	}

	// This method is the same as the one in @wordpress/e2e-test-utils-playwright. But for some reason
	// it doesn't work as expected when imported from there. For its first run we get the following error:
	// Error: locator.waitFor: Target closed
	async saveSiteEditorEntities() {
		const editorTopBar = this.page.getByRole( 'region', {
			name: 'Editor top bar',
		} );
		const savePanel = this.page.getByRole( 'region', {
			name: 'Save panel',
		} );

		// First Save button in the top bar.
		await editorTopBar
			.getByRole( 'button', { name: 'Save', exact: true } )
			.click();

		// Second Save button in the entities panel.
		await savePanel
			.getByRole( 'button', { name: 'Save', exact: true } )
			.click();

		await this.page
			.getByRole( 'button', { name: 'Dismiss this notice' } )
			.getByText( 'Site updated.' )
			.waitFor();
	}

	async visitTemplateEditor(
		templateName: string,
		templateType: TemplateType
	) {
		if ( templateType === 'wp_template_part' ) {
			await this.admin.visitSiteEditor( {
				path: `/${ templateType }/all`,
			} );
			await this.page.goto(
				`/wp-admin/site-editor.php?path=/${ templateType }/all`
			);
			const templateLink = this.page.getByRole( 'link', {
				name: templateName,
				exact: true,
			} );
			await templateLink.click();
		} else {
			await this.admin.visitSiteEditor( {
				path: '/' + templateType,
			} );
			const templateButton = this.page.getByRole( 'button', {
				name: templateName,
				exact: true,
			} );
			await templateButton.click();
		}

		await this.enterEditMode();
		await this.waitForSiteEditorFinishLoading();

		// Verify we are editing the correct template and it has the correct title.
		const templateTypeName =
			templateType === 'wp_template' ? 'template' : 'template part';
		await this.page
			.getByRole( 'heading', {
				name: `Editing ${ templateTypeName }: ${ templateName }`,
			} )
			.waitFor();
	}

	async revertTemplateCreation( templateName: string ) {
		const templateRow = this.page.getByRole( 'row' ).filter( {
			has: this.page.getByRole( 'link', {
				name: templateName,
				exact: true,
			} ),
		} );
		await templateRow.getByRole( 'button', { name: 'Delete' } ).click();
		await this.page
			.getByRole( 'button', {
				name: 'Delete',
			} )
			.click();
		await this.page
			.getByRole( 'button', { name: 'Dismiss this notice' } )
			.getByText( `"${ templateName }" deleted.` )
			.waitFor();
	}

	async revertTemplateCustomizations( templateName: string ) {
		const templateRow = this.page.getByRole( 'row' ).filter( {
			has: this.page.getByRole( 'link', {
				name: templateName,
				exact: true,
			} ),
		} );
		const resetButton = templateRow.getByRole( 'button', {
			name: 'Reset',
		} );
		const waitForReset = this.page
			.getByLabel( 'Dismiss this notice' )
			.getByText( `"${ templateName }" reverted.` )
			.waitFor();

		const waitForSavedLabel = this.page
			.getByRole( 'button', { name: 'Saved' } )
			.waitFor();

		await resetButton.click();
		await waitForSavedLabel;
		await waitForReset;
	}

	async updatePost() {
		await this.page.getByRole( 'button', { name: 'Update' } ).click();

		await this.page
			.getByRole( 'button', { name: 'Dismiss this notice' } )
			.filter( { hasText: 'updated' } )
			.waitFor();
	}

	async publishAndVisitPost() {
		await this.editor.publishPost();
		const url = new URL( this.page.url() );
		const postId = url.searchParams.get( 'post' );
		await this.page.goto( `/?p=${ postId }`, { waitUntil: 'commit' } );
	}

	async openWidgetEditor() {
		await this.page.goto( '/wp-admin/widgets.php' );
		await this.closeModalByName( 'Welcome to block Widgets' );
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

	/**
	 * Opens a specific Single Product template.
	 */
	async openSpecificProductTemplate(
		productName: string,
		productSlug: string,
		createIfDoesntExist = true
	) {
		await this.admin.visitSiteEditor();
		await this.page.getByRole( 'button', { name: 'Templates' } ).click();

		const templateButton = this.page.getByRole( 'button', {
			name: `Product: ${ productName }`,
		} );

		// Template can be created only once. Go to template if exists,
		// otherwise create one.
		if ( await templateButton.isVisible() ) {
			await templateButton.click();
			await this.enterEditMode();
		} else if ( createIfDoesntExist ) {
			await this.page
				.getByRole( 'button', { name: 'Add New Template' } )
				.click();
			await this.page
				.getByRole( 'button', { name: 'Single Item: Product' } )
				.click();
			await this.page
				.getByRole( 'option', {
					name: `${ productName } http://localhost:8889/product/${ productSlug }/`,
				} )
				.click();
			await this.page
				.getByRole( 'button', {
					name: 'Skip',
				} )
				.click();
		}
		await this.closeWelcomeGuideModal();
	}
}
