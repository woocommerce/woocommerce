/**
 * External dependencies
 */
import { test as base, expect, BLOCK_THEME_SLUG } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import ProductCollectionPage, { SELECTORS } from './product-collection.page';

const test = base.extend< { pageObject: ProductCollectionPage } >( {
	pageObject: async ( { page, admin, editor }, use ) => {
		const pageObject = new ProductCollectionPage( {
			page,
			admin,
			editor,
		} );
		await use( pageObject );
	},
} );

test.describe( 'Product Collection: Inspector Controls', () => {
	test( 'persists display controls and renders their frontend output', async ( {
		pageObject,
		page,
		editor,
	} ) => {
		await pageObject.createNewPostAndInsertBlock();
		await pageObject.setNumberOfColumns( 4 );
		await expect( pageObject.productTemplate ).toHaveClass( /columns-4/ );

		await page.getByRole( 'button', { name: 'Settings options' } ).click();
		await page.getByRole( 'menuitemcheckbox', { name: 'Offset' } ).click();
		await page
			.getByRole( 'menuitemcheckbox', { name: 'Max pages to show' } )
			.click();
		await page.getByRole( 'button', { name: 'Settings options' } ).click();

		const settingsPanel = page.locator(
			'.wc-block-editor-product-collection__settings_panel'
		);
		await settingsPanel
			.getByRole( 'spinbutton', { name: 'Products per page' } )
			.fill( '3' );
		await pageObject.refreshLocators( 'editor' );
		await expect( pageObject.products ).toHaveCount( 3 );

		await settingsPanel
			.getByRole( 'spinbutton', { name: 'Offset' } )
			.fill( '1' );
		await expect(
			editor.canvas.locator( '.wc-block-product-template__spinner' )
		).toBeHidden();
		await pageObject.refreshLocators( 'editor' );
		await expect( pageObject.productTitles.first() ).toHaveText( 'Beanie' );

		await settingsPanel
			.getByRole( 'spinbutton', { name: 'Max pages to show' } )
			.fill( '2' );

		await pageObject.publishAndGoToFrontend();
		await expect( pageObject.productTemplate ).toHaveClass( /columns-4/ );
		await expect( pageObject.products ).toHaveCount( 3 );
		await expect( pageObject.productTitles.first() ).toHaveText( 'Beanie' );
		await expect(
			page
				.locator( SELECTORS.pagination.onFrontend )
				.locator( '.page-numbers' )
		).toHaveCount( 2 );
	} );

	test( 'inherits the Product Catalog query and preserves custom criteria', async ( {
		pageObject,
		editor,
	} ) => {
		await pageObject.goToEditorTemplate();
		await pageObject.focusProductCollection();
		await editor.openDocumentSettingsSidebar();

		const sidebarSettings = pageObject.locateSidebarSettings();
		const queryType = sidebarSettings.getByLabel(
			SELECTORS.usePageContextControl
		);
		const defaultQueryType = queryType.getByLabel( 'Default' );
		const customQueryType = queryType.getByLabel( 'Custom' );
		const onSaleControl = sidebarSettings.getByLabel(
			SELECTORS.onSaleControlLabel
		);

		await expect( defaultQueryType ).toBeChecked();
		await expect(
			pageObject.productTitles.getByRole( 'link' ).first()
		).toBeVisible();
		const inheritedProductPaths =
			await pageObject.getProductPermalinkPaths();
		expect( inheritedProductPaths ).not.toHaveLength( 0 );
		await expect( onSaleControl ).toBeHidden();

		await customQueryType.click();
		await expect( onSaleControl ).toBeVisible();
		await pageObject.setShowOnlyProductsOnSale( {
			onSale: true,
			isLocatorsRefreshNeeded: false,
		} );
		await expect
			.poll( async () => {
				const productPaths =
					await pageObject.getProductPermalinkPaths();
				return (
					productPaths.length > 0 &&
					JSON.stringify( productPaths ) !==
						JSON.stringify( inheritedProductPaths )
				);
			} )
			.toBeTruthy();
		const customProductPaths = await pageObject.getProductPermalinkPaths();
		expect( customProductPaths ).not.toHaveLength( 0 );
		expect( customProductPaths ).not.toEqual( inheritedProductPaths );

		await defaultQueryType.click();
		await expect( onSaleControl ).toBeHidden();
		await expect
			.poll( async () => pageObject.getProductPermalinkPaths() )
			.toEqual( inheritedProductPaths );
		await customQueryType.click();
		await expect( onSaleControl ).toBeChecked();
		await expect
			.poll( async () => pageObject.getProductPermalinkPaths() )
			.toEqual( customProductPaths );

		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );
		await pageObject.goToProductCatalogFrontend();
		await expect(
			pageObject.productTitles.getByRole( 'link' ).first()
		).toBeVisible();
		await expect
			.poll( async () => pageObject.getProductPermalinkPaths() )
			.toEqual( customProductPaths );
	} );

	test( 'correctly combines editor and front-end filters', async ( {
		pageObject,
		editor,
		page,
		requestUtils,
	} ) => {
		await requestUtils.setFeatureFlag( 'experimental-blocks', true );
		await pageObject.createNewPostAndInsertBlock();

		await pageObject.addFilter( 'Show product categories' );
		await pageObject.checkTaxonomyTerm( 'categories', 'Music' );

		const productCollectionBlock = await editor.getBlockByName(
			'woocommerce/product-collection'
		);
		const productCollectionClientId =
			( await productCollectionBlock
				.last()
				.getAttribute( 'data-block' ) ) ?? '';
		await editor.insertBlock(
			{ name: 'woocommerce/product-filters' },
			{ clientId: productCollectionClientId }
		);

		await expect( pageObject.products ).toHaveCount( 2 );
		const postId = await editor.publishPost();
		await page.goto( `/?p=${ postId }` );
		await pageObject.refreshLocators( 'frontend' );
		await expect( pageObject.products ).toHaveCount( 2 );

		await page
			.getByRole( 'textbox', {
				name: 'Filter products by maximum price',
			} )
			.dblclick();
		await page.keyboard.type( '5' );
		await page.keyboard.press( 'Tab' );
		await expect( pageObject.products ).toHaveCount( 1 );
	} );

	test( 'scopes Query Type and Product Filters to the first eligible collection', async ( {
		pageObject,
		editor,
		page,
		requestUtils,
	} ) => {
		await requestUtils.setFeatureFlag( 'experimental-blocks', true );
		await pageObject.createNewPostAndInsertBlock();

		const productCollection = editor.canvas.getByLabel(
			'Block: Product Collection',
			{ exact: true }
		);
		const queryType = pageObject
			.locateSidebarSettings()
			.getByLabel( SELECTORS.usePageContextControl );
		const defaultQueryType = queryType.getByLabel( 'Default' );
		const customQueryType = queryType.getByLabel( 'Custom' );

		await editor.selectBlocks( productCollection.first() );
		await expect( defaultQueryType ).toBeChecked();

		await pageObject.insertProductCollection();
		await pageObject.chooseCollectionInPost( 'productCatalog' );
		await editor.selectBlocks( productCollection.last() );
		await expect( customQueryType ).toBeChecked();
		await expect( pageObject.products ).toHaveCount( 18 );

		const productCollectionBlock = await editor.getBlockByName(
			'woocommerce/product-collection'
		);
		const secondCollectionClientId =
			( await productCollectionBlock
				.last()
				.getAttribute( 'data-block' ) ) ?? '';
		await editor.insertBlock(
			{ name: 'woocommerce/product-filters' },
			{ clientId: secondCollectionClientId }
		);

		const postId = await editor.publishPost();
		await page.goto( `/?p=${ postId }` );
		const collections = page.locator(
			'.wp-block-woocommerce-product-collection'
		);
		await expect(
			collections.first().locator( SELECTORS.product )
		).toHaveCount( 9 );
		await expect(
			collections.last().locator( SELECTORS.product )
		).toHaveCount( 9 );

		await page
			.getByRole( 'textbox', {
				name: 'Filter products by maximum price',
			} )
			.dblclick();
		await page.keyboard.type( '10' );
		await page.keyboard.press( 'Tab' );

		await expect(
			collections.first().locator( SELECTORS.product )
		).toHaveCount( 1 );
		await expect(
			collections.last().locator( SELECTORS.product )
		).toHaveCount( 9 );
	} );

	test.describe( '"Query Type" control fallback', () => {
		[
			`${ BLOCK_THEME_SLUG }//taxonomy-product_attribute`,
			`${ BLOCK_THEME_SLUG }//product-search-results`,
		].forEach( ( slug ) => {
			test( `should be visible in archive template: ${ slug }`, async ( {
				pageObject,
				editor,
			} ) => {
				await pageObject.goToEditorTemplate( slug );
				await pageObject.insertProductCollection();
				await pageObject.chooseCollectionInTemplate();
				await pageObject.focusProductCollection();
				await editor.openDocumentSettingsSidebar();

				await expect(
					pageObject
						.locateSidebarSettings()
						.getByLabel( SELECTORS.usePageContextControl )
				).toBeVisible();
			} );
		} );

		[
			{
				slug: `${ BLOCK_THEME_SLUG }//taxonomy-product_cat`,
				title: 'Products by Category',
			},
			{
				slug: `${ BLOCK_THEME_SLUG }//taxonomy-product_tag`,
				title: 'Products by Tag',
			},
			{
				slug: `${ BLOCK_THEME_SLUG }//taxonomy-product_brand`,
				title: 'Products by Brand',
			},
		].forEach( ( template ) => {
			test( `should be visible in archive template: ${ template.slug }`, async ( {
				admin,
				pageObject,
				editor,
			} ) => {
				await admin.visitSiteEditor( {
					postType: 'wp_template',
				} );
				await editor.createTemplate( {
					templateName: template.title,
				} );
				await pageObject.insertProductCollection();
				await pageObject.chooseCollectionInTemplate();
				await pageObject.focusProductCollection();
				await editor.openDocumentSettingsSidebar();

				await expect(
					pageObject
						.locateSidebarSettings()
						.getByLabel( SELECTORS.usePageContextControl )
				).toBeVisible();
			} );
		} );

		[
			`${ BLOCK_THEME_SLUG }//single-product`,
			`${ BLOCK_THEME_SLUG }//home`,
			`${ BLOCK_THEME_SLUG }//index`,
		].forEach( ( slug ) => {
			test( `should be visible in non-archive template: ${ slug }`, async ( {
				pageObject,
				editor,
			} ) => {
				await pageObject.goToEditorTemplate( slug );
				await pageObject.insertProductCollection();
				await pageObject.chooseCollectionInTemplate();
				await pageObject.focusProductCollection();
				await editor.openDocumentSettingsSidebar();

				await expect(
					pageObject
						.locateSidebarSettings()
						.getByLabel( SELECTORS.usePageContextControl )
				).toBeVisible();
			} );
		} );

		test( 'is enabled by default unless already enabled elsewhere', async ( {
			pageObject,
			editor,
		} ) => {
			const productCollection = editor.canvas.getByLabel(
				'Block: Product Collection',
				{ exact: true }
			);
			const sidebarSettings = pageObject.locateSidebarSettings();
			const queryTypeLocator = sidebarSettings.getByLabel(
				SELECTORS.usePageContextControl
			);

			const defaultQueryType = queryTypeLocator.getByLabel( 'Default' );
			const customQueryType = queryTypeLocator.getByLabel( 'Custom' );

			// First Product Catalog
			// Option should be visible & ENABLED by default
			await pageObject.goToEditorTemplate();
			await editor.selectBlocks( productCollection.first() );
			await editor.openDocumentSettingsSidebar();

			await expect( defaultQueryType ).toBeChecked();
			await expect( customQueryType ).not.toBeChecked();

			// Second Product Catalog
			// Option should be visible & DISABLED by default
			await pageObject.insertProductCollection();
			await pageObject.chooseCollectionInTemplate( 'productCatalog' );
			await editor.selectBlocks( productCollection.last() );

			await expect( defaultQueryType ).not.toBeChecked();
			await expect( customQueryType ).toBeChecked();

			// Disable the option in the first Product Catalog
			await editor.selectBlocks( productCollection.first() );
			await expect( defaultQueryType ).toBeChecked();
			await customQueryType.click();
			await expect( customQueryType ).toBeChecked();

			// Third Product Catalog
			// Option should be visible & ENABLED by default
			await pageObject.insertProductCollection();
			await pageObject.chooseCollectionInTemplate( 'productCatalog' );

			await expect( defaultQueryType ).toBeChecked();
			await expect( customQueryType ).not.toBeChecked();
		} );
	} );
} );
