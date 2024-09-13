/**
 * External dependencies
 */
import { test, expect, BlockData } from '@woocommerce/e2e-utils';

const blockData: BlockData = {
	name: 'Product Filters (Experimental)',
	slug: 'woocommerce/product-filters',
	mainClass: '.wp-block-woocommerce-product-filters',
	selectors: {
		editor: {
			block: '.wp-block-woocommerce-product-filters',
		},
		frontend: {},
	},
};

const templatePartData = {
	selectors: {
		frontend: {},
		editor: {
			blocks: {
				activeFilters: {
					title: 'Active (Experimental)',
					blockLabel: 'Block: Active (Experimental)',
				},
				productFilters: {
					title: 'Product Filters (Experimental)',
					blockLabel: 'Block: Product Filters (Experimental)',
				},
				filterOptions: {
					title: 'Filter Options',
					blockLabel: 'Block: Filter Options',
				},
				productFiltersOverlayNavigation: {
					title: 'Overlay Navigation (Experimental)',
					name: 'woocommerce/product-filters-overlay-navigation',
					blockLabel: 'Block: Overlay Navigation (Experimental)',
				},
			},
		},
	},
	slug: 'product-filters',
	productPage: '/product/hoodie/',
};

test.describe( 'Product Filters Template Part', () => {
	test.beforeEach( async ( { admin, requestUtils } ) => {
		await requestUtils.activatePlugin(
			'woocommerce-blocks-test-enable-experimental-features'
		);
		await admin.visitSiteEditor( {
			postType: 'wp_template_part',
			postId: 'woocommerce/woocommerce//product-filters',
			canvas: 'edit',
		} );
	} );

	test( 'should be visible in the templates part list', async ( {
		page,
		admin,
	} ) => {
		await admin.visitSiteEditor( {
			postType: 'wp_template_part',
		} );
		const templatePart = page
			.getByLabel( 'Patterns content' )
			.getByText( 'Product Filters (Experimental)', { exact: true } )
			.and( page.getByRole( 'button' ) );
		await expect( templatePart ).toBeVisible();
	} );

	test( 'should render the Product Filters block', async ( { editor } ) => {
		const productFiltersBlock = editor.canvas.getByLabel(
			`Block: ${ blockData.name }`
		);
		await expect( productFiltersBlock ).toBeVisible();
	} );

	test( 'Filters > can be added multiple times', async ( { editor } ) => {
		const block = editor.canvas.getByLabel( `Block: ${ blockData.name }` );
		await expect( block ).toBeVisible();

		const searchTerms = [
			'Status (Experimental)',
			'Price (Experimental)',
			'Rating (Experimental)',
			'Attribute (Experimental)',
			'Active (Experimental)',
		];

		for ( const filter of searchTerms ) {
			await editor.selectBlocks( blockData.selectors.editor.block );

			const addBlock = block.getByRole( 'button', {
				name: 'Add block',
			} );

			await addBlock.click();

			await editor.page.getByPlaceholder( 'Search' ).fill( filter );

			const searchResult = editor.page.getByRole( 'option', {
				name: filter,
			} );
			await expect( searchResult ).toBeVisible();

			await searchResult.click();

			let _locator = `[aria-label="Block: ${ filter }"]`;

			// We need to treat the attributes filter different because
			// the variation of the block label depends on the product attribute.
			if ( filter === 'Attribute (Experimental)' ) {
				_locator = '.wp-block-woocommerce-product-filter-attribute';
			}

			await expect( editor.canvas.locator( _locator ) ).toHaveCount( 2 );
		}
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
			await page
				.getByText(
					templatePartData.selectors.editor.blocks.productFilters
						.title
				)
				.click();
			const block = editor.canvas.getByLabel(
				templatePartData.selectors.editor.blocks.productFilters
					.blockLabel
			);
			await expect( block ).toBeVisible();

			// This forces the list view to show the inner blocks of the Product Filters template part.
			await editor.canvas
				.getByLabel( 'Block: Template Part' )
				.getByLabel(
					templatePartData.selectors.editor.blocks.filterOptions
						.blockLabel
				)
				.first()
				.click();

			await editor.openDocumentSettingsSidebar();
			await page.getByLabel( 'Document Overview' ).click();
			await page
				.getByRole( 'link', {
					name: templatePartData.selectors.editor.blocks
						.productFilters.title,
				} )
				.nth( 1 )
				.click();

			const layoutSettings = editor.page.getByText(
				'OverlayNeverMobileAlways'
			);
			await layoutSettings.getByLabel( 'Always' ).click();
			await editor.page
				.getByRole( 'link', {
					name: templatePartData.selectors.editor.blocks
						.productFiltersOverlayNavigation.title,
				} )
				.click();

			await editor.saveSiteEditorEntities( {
				isOnlyCurrentEntityDirty: false,
			} );

			await page.goto( '/shop/' );

			const productFiltersOverlayNavigation = (
				await frontendUtils.getBlockByName(
					templatePartData.selectors.editor.blocks
						.productFiltersOverlayNavigation.name
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
					templatePartData.selectors.editor.blocks
						.productFiltersOverlayNavigation.name
				)
			 ).filter( { hasText: 'Close' } );

			await expect( productFiltersDialogCloseButton ).toBeVisible();

			await productFiltersDialogCloseButton.click();

			await expect( productFiltersDialog ).toBeHidden();
		} );

		// Since we need to overhaul the overlay area, we can skip this test for now.
		// eslint-disable-next-line playwright/no-skipped-test
		test.skip( 'should hide Product Filters Overlay Navigation block when the Overlay mode is set to `Never`', async ( {
			editor,
			page,
			frontendUtils,
		} ) => {
			await editor.setContent( '' );
			await editor.openGlobalBlockInserter();
			await page
				.getByText(
					templatePartData.selectors.editor.blocks.productFilters
						.title
				)
				.click();
			const block = editor.canvas.getByLabel(
				templatePartData.selectors.editor.blocks.productFilters
					.blockLabel
			);
			await expect( block ).toBeVisible();

			// This forces the list view to show the inner blocks of the Product Filters template part.
			await editor.canvas
				.getByLabel(
					templatePartData.selectors.editor.blocks.activeFilters
						.blockLabel
				)
				.getByLabel(
					templatePartData.selectors.editor.blocks.filterOptions
						.blockLabel
				)
				.click();

			await editor.openDocumentSettingsSidebar();
			await page.getByLabel( 'Document Overview' ).click();
			await page
				.getByRole( 'link', {
					name: templatePartData.selectors.editor.blocks
						.productFilters.title,
				} )
				.nth( 1 )
				.click();

			const layoutSettings = editor.page.getByText(
				'OverlayNeverMobileAlways'
			);
			await layoutSettings.getByLabel( 'Never' ).click();
			await editor.page
				.getByRole( 'link', {
					name: templatePartData.selectors.editor.blocks
						.productFiltersOverlayNavigation.title,
				} )
				.click();

			await editor.saveSiteEditorEntities( {
				isOnlyCurrentEntityDirty: true,
			} );

			await page.goto( '/shop/' );

			const productFiltersOverlayNavigation = (
				await frontendUtils.getBlockByName(
					templatePartData.selectors.editor.blocks
						.productFiltersOverlayNavigation.name
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
			await page
				.getByText(
					templatePartData.selectors.editor.blocks.productFilters
						.title
				)
				.click();
			const block = editor.canvas.getByLabel(
				templatePartData.selectors.editor.blocks.productFilters
					.blockLabel
			);
			await expect( block ).toBeVisible();

			// This forces the list view to show the inner blocks of the Product Filters template part.
			await editor.canvas
				.getByLabel(
					templatePartData.selectors.editor.blocks.activeFilters
						.blockLabel
				)
				.getByLabel(
					templatePartData.selectors.editor.blocks.filterOptions
						.blockLabel
				)
				.click();

			await editor.openDocumentSettingsSidebar();
			await page.getByLabel( 'Document Overview' ).click();
			await page
				.getByRole( 'link', {
					name: templatePartData.selectors.editor.blocks
						.productFilters.title,
				} )
				.nth( 1 )
				.click();

			const layoutSettings = editor.page.getByText(
				'OverlayNeverMobileAlways'
			);
			await layoutSettings.getByLabel( 'Mobile' ).click();
			await editor.page
				.getByRole( 'link', {
					name: templatePartData.selectors.editor.blocks
						.productFiltersOverlayNavigation.title,
				} )
				.click();

			await editor.saveSiteEditorEntities( {
				isOnlyCurrentEntityDirty: false,
			} );

			await page.goto( '/shop/' );

			const productFiltersOverlayNavigation = (
				await frontendUtils.getBlockByName(
					templatePartData.selectors.editor.blocks
						.productFiltersOverlayNavigation.name
				)
			 ).filter( {
				has: page.locator( ':visible' ),
			} );

			await expect( productFiltersOverlayNavigation ).toBeHidden();
		} );
	} );
} );
