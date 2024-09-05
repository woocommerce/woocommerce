/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-utils';

test.describe( 'Filters Overlay Template Part', () => {
	test.beforeEach( async ( { admin, requestUtils } ) => {
		await requestUtils.activatePlugin(
			'woocommerce-blocks-test-enable-experimental-features'
		);
		await admin.visitSiteEditor( {
			postType: 'wp_template_part',
			postId: 'woocommerce/woocommerce//product-filters-overlay',
			canvas: 'edit',
		} );
	} );

	test( 'should be visible in the template parts list', async ( {
		page,
		admin,
	} ) => {
		await admin.visitSiteEditor( {
			postType: 'wp_template_part',
		} );
		const block = page
			.getByLabel( 'Patterns content' )
			.getByText( 'Filters Overlay' )
			.and( page.getByRole( 'button' ) );
		await expect( block ).toBeVisible();
	} );

	test( 'should render the correct inner blocks', async ( { editor } ) => {
		const productFiltersTemplatePart = editor.canvas
			.locator( '[data-type="core/template-part"]' )
			.filter( {
				has: editor.canvas.getByLabel(
					'Block: Product Filters (Experimental)'
				),
			} );

		await expect( productFiltersTemplatePart ).toBeVisible();
	} );

	test.describe( 'frontend', () => {
		test.beforeEach( async ( { admin } ) => {
			await admin.visitSiteEditor( {
				postId: `woocommerce/woocommerce//archive-product`,
				postType: 'wp_template',
				canvas: 'edit',
			} );
		} );

		test( 'should open and close the dialog when clicking on the Product Filters Overlay Navigation block', async ( {
			editor,
			page,
			frontendUtils,
		} ) => {
			await editor.setContent( '' );
			await editor.openGlobalBlockInserter();
			await page.getByText( 'Product Filters (Experimental)' ).click();
			const block = editor.canvas.getByLabel(
				'Block: Product Filters (Experimental)'
			);
			await expect( block ).toBeVisible();

			// This forces the list view to show the inner blocks of the Product Filters template part.
			await editor.canvas
				.getByLabel( 'Block: Active (Experimental)' )
				.getByLabel( 'Block: Filter Options' )
				.click();

			await editor.openDocumentSettingsSidebar();
			await page.getByLabel( 'Document Overview' ).click();
			await page
				.getByRole( 'link', { name: 'Product Filters (Experimental)' } )
				.nth( 1 )
				.click();

			const layoutSettings = editor.page.getByText(
				'OverlayNeverMobileAlways'
			);
			await layoutSettings.getByLabel( 'Always' ).click();
			await editor.page
				.getByRole( 'link', {
					name: 'Overlay Navigation (Experimental)',
				} )
				.click();

			await editor.saveSiteEditorEntities( {
				isOnlyCurrentEntityDirty: false,
			} );

			await page.goto( '/shop/' );

			const productFiltersOverlayNavigation = (
				await frontendUtils.getBlockByName(
					'woocommerce/product-filters-overlay-navigation'
				)
			 ).filter( {
				has: page.locator( ':visible' ),
			} );

			await expect( productFiltersOverlayNavigation ).toBeVisible();

			await page
				.locator( '.wc-block-product-filters-overlay-navigation' )
				.first()
				.click();

			const productFiltersDialog = page.locator(
				'.wc-block-product-filters--dialog-open'
			);

			await expect( productFiltersDialog ).toBeVisible();

			const productFiltersDialogCloseButton = (
				await frontendUtils.getBlockByName(
					'woocommerce/product-filters-overlay-navigation'
				)
			 ).filter( { hasText: 'Close' } );

			await expect( productFiltersDialogCloseButton ).toBeVisible();

			await productFiltersDialogCloseButton.click();

			await expect( productFiltersDialog ).toBeHidden();
		} );

		test( 'should hide Product Filters Overlay Navigation block when the Overlay mode is set to `Never`', async ( {
			editor,
			page,
			frontendUtils,
		} ) => {
			await editor.setContent( '' );
			await editor.openGlobalBlockInserter();
			await page.getByText( 'Product Filters (Experimental)' ).click();
			const block = editor.canvas.getByLabel(
				'Block: Product Filters (Experimental)'
			);
			await expect( block ).toBeVisible();

			// This forces the list view to show the inner blocks of the Product Filters template part.
			await editor.canvas
				.getByLabel( 'Block: Active (Experimental)' )
				.getByLabel( 'Block: Filter Options' )
				.click();

			await editor.openDocumentSettingsSidebar();
			await page.getByLabel( 'Document Overview' ).click();
			await page
				.getByRole( 'link', { name: 'Product Filters (Experimental)' } )
				.nth( 1 )
				.click();

			const layoutSettings = editor.page.getByText(
				'OverlayNeverMobileAlways'
			);
			await layoutSettings.getByLabel( 'Never' ).click();
			await editor.page
				.getByRole( 'link', {
					name: 'Overlay Navigation (Experimental)',
				} )
				.click();

			await editor.saveSiteEditorEntities( {
				isOnlyCurrentEntityDirty: true,
			} );

			await page.goto( '/shop/' );

			const productFiltersOverlayNavigation = (
				await frontendUtils.getBlockByName(
					'woocommerce/product-filters-overlay-navigation'
				)
			 ).filter( {
				has: page.locator( ':visible' ),
			} );

			await expect( productFiltersOverlayNavigation ).toBeHidden();
		} );

		test( 'should hide Product Filters Overlay Navigation block when the Overlay mode is set to `Mobile` and user is on desktop', async ( {
			editor,
			page,
			frontendUtils,
		} ) => {
			await editor.setContent( '' );
			await editor.openGlobalBlockInserter();
			await page.getByText( 'Product Filters (Experimental)' ).click();
			const block = editor.canvas.getByLabel(
				'Block: Product Filters (Experimental)'
			);
			await expect( block ).toBeVisible();

			// This forces the list view to show the inner blocks of the Product Filters template part.
			await editor.canvas
				.getByLabel( 'Block: Active (Experimental)' )
				.getByLabel( 'Block: Filter Options' )
				.click();

			await editor.openDocumentSettingsSidebar();
			await page.getByLabel( 'Document Overview' ).click();
			await page
				.getByRole( 'link', { name: 'Product Filters (Experimental)' } )
				.nth( 1 )
				.click();

			const layoutSettings = editor.page.getByText(
				'OverlayNeverMobileAlways'
			);
			await layoutSettings.getByLabel( 'Mobile' ).click();
			await editor.page
				.getByRole( 'link', {
					name: 'Overlay Navigation (Experimental)',
				} )
				.click();

			await editor.saveSiteEditorEntities( {
				isOnlyCurrentEntityDirty: false,
			} );

			await page.goto( '/shop/' );

			const productFiltersOverlayNavigation = (
				await frontendUtils.getBlockByName(
					'woocommerce/product-filters-overlay-navigation'
				)
			 ).filter( {
				has: page.locator( ':visible' ),
			} );

			await expect( productFiltersOverlayNavigation ).toBeHidden();
		} );
	} );
} );
