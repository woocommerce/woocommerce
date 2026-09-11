/**
 * External dependencies
 */
import { Request } from '@playwright/test';
import { test as base, expect, wpCLI, BASE_URL } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import ProductCollectionPage, {
	BLOCK_LABELS,
	SELECTORS,
} from './product-collection.page';

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

test.describe( 'Product Collection', () => {
	test( 'Can be migrated to from Products (Deprecated) block', async ( {
		page,
		editor,
		admin,
	} ) => {
		await admin.createNewPost();

		await editor.insertBlock( {
			name: 'core/query',
			attributes: {
				namespace: 'woocommerce/product-query',
			},
		} );

		await expect(
			editor.canvas.getByLabel( 'Block: Products (Deprecated)' )
		).toBeVisible();

		await editor.canvas
			.getByRole( 'button', { name: 'Start blank' } )
			.click();
		await editor.canvas.getByLabel( 'Title & Date' ).click();

		await page
			.getByRole( 'button', { name: 'Upgrade to Product Collection' } )
			.click();

		await expect(
			editor.canvas.getByLabel( 'Block: Products (Deprecated)' )
		).toBeHidden();
		await expect(
			editor.canvas.getByLabel( 'Block: Product Collection' ).first()
		).toBeVisible();
		await expect(
			page.getByRole( 'button', { name: 'Choose collection' } )
		).toBeVisible();
	} );

	test( 'renders all Product Elements after save, reload, and frontend navigation', async ( {
		page,
		editor,
		pageObject,
	} ) => {
		await pageObject.createNewPostAndInsertBlock();
		await expect(
			editor.canvas.locator( '[data-testid="product-image"]:visible' )
		).toHaveCount( 9 );

		await pageObject.insertProductElements();
		const postId = await editor.publishPost();
		await page.reload();
		await expect(
			editor.canvas.getByLabel( 'Block: Product Collection' ).first()
		).toBeVisible();
		await pageObject.refreshLocators( 'editor' );
		await expect( pageObject.products ).toHaveCount( 9 );

		await page.goto( `/?p=${ postId }` );
		await pageObject.refreshLocators( 'frontend' );

		await expect( pageObject.products ).toHaveCount( 9 );
		await expect( pageObject.productImages ).toHaveCount( 9 );
		await expect( pageObject.productTitles ).toHaveCount( 9 );
		await expect( pageObject.productPrices ).toHaveCount( 9 );
		await expect( pageObject.addToCartButtons ).toHaveCount( 9 );

		const beanie = pageObject.products.filter( {
			has: page
				.locator( SELECTORS.productTitle )
				.filter( { hasText: /^Beanie$/ } ),
		} );
		await expect( beanie ).toHaveCount( 1 );
		await expect(
			beanie.locator( SELECTORS.productImage.onFrontend )
		).toHaveCount( 1 );
		await expect(
			beanie.locator( SELECTORS.addToCartButton.onFrontend )
		).toContainText( 'Add to cart' );

		for ( const content of [
			'Beanie',
			'$20.00 Original price was: $20.00.$18.00Current price is: $18.00.',
			'woo-beanie',
			'This is a simple product.',
			'Accessories',
			'Recommended',
			'SaleProduct on sale',
		] ) {
			await expect( beanie ).toContainText( content );
		}
	} );

	test.describe( 'Responsive', () => {
		test.beforeEach( async ( { pageObject } ) => {
			await pageObject.createNewPostAndInsertBlock();
		} );

		test( 'Block with shrink columns ENABLED correctly displays as grid', async ( {
			pageObject,
		} ) => {
			await pageObject.publishAndGoToFrontend();
			const productTemplate = pageObject.productTemplate;

			await expect( productTemplate ).toHaveCSS( 'display', 'grid' );
			await expect( productTemplate ).toHaveCSS(
				'grid-template-columns',
				/^\d+(\.\d+)?px \d+(\.\d+)?px \d+(\.\d+)?px$/
			);

			await pageObject.setViewportSize( {
				height: 667,
				width: 390,
			} );

			await expect( productTemplate ).toHaveCSS(
				'grid-template-columns',
				/^\d+(\.\d+)?px \d+(\.\d+)?px$/
			);
		} );

		test( 'Block with shrink columns DISABLED collapses to single column on small screens', async ( {
			pageObject,
		} ) => {
			await pageObject.setShrinkColumnsToFit( false );
			await pageObject.publishAndGoToFrontend();

			const productTemplate = pageObject.productTemplate;
			await expect( productTemplate ).not.toHaveCSS( 'display', 'grid' );

			const firstProduct = pageObject.products.first();
			let productSize = await firstProduct.boundingBox();
			let parentSize = await firstProduct
				.locator( 'xpath=..' )
				.boundingBox();
			expect( productSize?.width ).toBeLessThan(
				parentSize?.width as number
			);

			await pageObject.setViewportSize( {
				height: 667,
				width: 390,
			} );

			productSize = await firstProduct.boundingBox();
			parentSize = await firstProduct.locator( 'xpath=..' ).boundingBox();
			expect( productSize?.width ).toBeCloseTo(
				parentSize?.width as number
			);
		} );
	} );

	test( 'In Single Product block', async ( { admin, pageObject } ) => {
		await admin.createNewPost();
		await pageObject.insertProductCollectionInSingleProductBlock();
		await pageObject.chooseCollectionInPost( 'featured' );
		await pageObject.refreshLocators( 'editor' );

		await expect( pageObject.products ).toHaveCount( 4 );
		await expect( pageObject.productTitles ).toHaveText( [
			'Cap',
			'Hoodie with Zipper',
			'Sunglasses',
			'V-Neck T-Shirt',
		] );
		await expect( pageObject.productPrices ).toHaveText( [
			'Previous price:$18.00Discounted price:$16.00',
			'$45.00',
			'$90.00',
			'Price between $15.00 and $20.00$15.00 — $20.00',
		] );
	} );

	test.describe( 'With other blocks', () => {
		test( 'With multiple Pagination blocks', async ( {
			admin,
			editor,
			pageObject,
		} ) => {
			await admin.createNewPost();
			await pageObject.insertProductCollection();
			await pageObject.chooseCollectionInPost( 'productCatalog' );
			const paginations = editor.canvas.getByLabel(
				BLOCK_LABELS.pagination
			);

			await expect( paginations ).toHaveCount( 1 );

			const siblingBlock = await editor.getBlockByName(
				'woocommerce/product-template'
			);
			await editor.selectBlocks( siblingBlock );
			await editor.insertBlockUsingGlobalInserter( 'Pagination' );

			await expect( paginations ).toHaveCount( 2 );
		} );
	} );

	test( 'resolves specific product and taxonomy requests', async ( {
		admin,
		page,
		pageObject,
		editor,
		wpCoreVersion,
	} ) => {
		const getLocationParams = ( request: Request ) =>
			new URL( request.url() ).searchParams;
		const isProductCollectionRequest = ( request: Request ) => {
			const params = getLocationParams( request );
			return (
				request.url().includes( 'wp/v2/product' ) &&
				params.get( 'isProductCollectionBlock' ) === 'true'
			);
		};
		const isSpecificProductRequest = ( request: Request ) => {
			const params = getLocationParams( request );
			return (
				isProductCollectionRequest( request ) &&
				params.get( 'productCollectionLocation[type]' ) === 'product' &&
				!! params.get(
					'productCollectionLocation[sourceData][productId]'
				)
			);
		};
		const isSpecificCategoryRequest = ( request: Request ) => {
			const params = getLocationParams( request );
			return (
				isProductCollectionRequest( request ) &&
				params.get( 'productCollectionLocation[type]' ) === 'archive' &&
				params.get(
					'productCollectionLocation[sourceData][taxonomy]'
				) === 'product_cat' &&
				!! params.get( 'productCollectionLocation[sourceData][termId]' )
			);
		};

		await admin.visitSiteEditor( { path: '/wp_template' } );
		await page
			.getByRole( 'button', {
				name:
					wpCoreVersion >= 6.8 ? 'Add Template' : 'Add New Template',
			} )
			.click();
		await page
			.getByRole( 'button', { name: 'Single Item: Product' } )
			.click();
		await page
			.getByRole( 'option', {
				name: `Cap ${ BASE_URL }/product/cap/`,
			} )
			.click();
		await page.getByRole( 'button', { name: 'Skip' } ).click();
		await editor.insertBlockUsingGlobalInserter( pageObject.BLOCK_NAME );
		await editor.closeGlobalBlockInserter();

		const productRequestPromise = page.waitForRequest(
			isSpecificProductRequest
		);
		await pageObject.chooseCollectionInTemplate( 'featured' );
		const productParams = getLocationParams( await productRequestPromise );
		expect(
			productParams.get(
				'productCollectionLocation[sourceData][productId]'
			)
		).toMatch( /^[1-9]\d*$/ );
		expect(
			productParams.get(
				'productCollectionLocation[sourceData][taxonomy]'
			)
		).toBeNull();
		expect(
			productParams.get( 'productCollectionLocation[sourceData][termId]' )
		).toBeNull();
		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );

		await admin.visitSiteEditor( { path: '/wp_template' } );
		const categoriesLoaded = page.waitForResponse( ( response ) =>
			response.url().includes( 'wp-json/wp/v2/product_cat' )
		);
		await page
			.getByRole( 'button', {
				name:
					wpCoreVersion >= 6.8 ? 'Add Template' : 'Add New Template',
			} )
			.click();
		await categoriesLoaded;
		await page
			.getByRole( 'button', { name: 'Products by Category' } )
			.click();
		await page
			.getByRole( 'button', { name: 'For a specific item' } )
			.click();
		await page.getByRole( 'option', { name: 'Hoodies' } ).click();

		const categoryRequestPromise = page.waitForRequest(
			isSpecificCategoryRequest
		);
		await page.getByRole( 'option', { name: 'Fallback content' } ).click();
		const categoryParams = getLocationParams(
			await categoryRequestPromise
		);
		expect(
			categoryParams.get(
				'productCollectionLocation[sourceData][termId]'
			)
		).toMatch( /^[1-9]\d*$/ );
		expect(
			categoryParams.get(
				'productCollectionLocation[sourceData][taxonomy]'
			)
		).toBe( 'product_cat' );
		expect(
			categoryParams.get(
				'productCollectionLocation[sourceData][productId]'
			)
		).toBeNull();
	} );

	// Tests for regressions of https://github.com/woocommerce/woocommerce/pull/47994
	test.describe( 'Product Collection should be visible after Refresh', () => {
		test( 'Product Collection should be visible after Refresh in a Template', async ( {
			page,
			editor,
			pageObject,
		} ) => {
			await pageObject.goToEditorTemplate();
			const productTemplate = editor.canvas.getByLabel(
				BLOCK_LABELS.productTemplate
			);
			await expect( productTemplate ).toBeVisible();

			// Refresh the template and verify the block is still visible
			await page.reload();
			await expect( productTemplate ).toBeVisible();
		} );

		test( 'Product Collection should be visible after Refresh in a Post', async ( {
			page,
			pageObject,
			editor,
		} ) => {
			await pageObject.createNewPostAndInsertBlock();
			await expect( pageObject.productTemplate ).toBeVisible();

			// Refresh the post and verify the block is still visible
			await editor.publishPost();
			await page.reload();
			await expect( pageObject.productTemplate ).toBeVisible();
		} );

		test( 'On Sale Products collection should be visible after Refresh', async ( {
			page,
			pageObject,
			editor,
		} ) => {
			await pageObject.goToEditorTemplate();
			await pageObject.insertProductCollection();
			await pageObject.chooseCollectionInTemplate( 'onSale' );

			const productTemplate = editor.canvas.getByLabel(
				BLOCK_LABELS.productTemplate
			);

			await expect( productTemplate ).toHaveCount( 2 );

			// Refresh the template and verify "On Sale Products" collection is still visible
			await editor.saveSiteEditorEntities( {
				isOnlyCurrentEntityDirty: true,
			} );
			await page.reload();
			await expect( productTemplate ).toHaveCount( 2 );
		} );

		test( 'On Sale Products collection should be visible after Refresh in a Post', async ( {
			page,
			pageObject,
			editor,
		} ) => {
			await pageObject.createNewPostAndInsertBlock( 'onSale' );
			await expect( pageObject.productTemplate ).toBeVisible();

			// Refresh the post and verify "On Sale Products" collection is still visible
			await editor.saveDraft();
			await page.reload();
			await expect( pageObject.productTemplate ).toBeVisible();
		} );
	} );

	const templates = [
		{
			templateTitle: 'Product Category',
			slug: 'taxonomy-product_cat',
			frontendPage: '/product-category/music/',
			legacyBlockName: 'woocommerce/legacy-template',
		},
		{
			templateTitle: 'Product Tag',
			slug: 'taxonomy-product_tag',
			frontendPage: '/product-tag/recommended/',
			legacyBlockName: 'woocommerce/legacy-template',
		},
		{
			templateTitle: 'Product Catalog',
			slug: 'archive-product',
			frontendPage: '/shop/',
			legacyBlockName: 'woocommerce/legacy-template',
		},
		{
			templateTitle: 'Product Search Results',
			slug: 'product-search-results',
			frontendPage: '/?s=shirt&post_type=product',
			legacyBlockName: 'woocommerce/legacy-template',
		},
	];

	templates.forEach(
		( { templateTitle, slug, frontendPage, legacyBlockName } ) => {
			test.describe( `${ templateTitle } template`, () => {
				test( 'Product Collection block matches with classic template block', async ( {
					pageObject,
					requestUtils,
					admin,
					editor,
					page,
				} ) => {
					await pageObject.refreshLocators( 'frontend' );
					await page.goto( frontendPage );

					const productCollectionProductNames =
						await pageObject.getProductNames();
					expect(
						productCollectionProductNames.length
					).toBeGreaterThan( 0 );

					const template = await requestUtils.createTemplate(
						'wp_template',
						{
							slug,
							title: 'classic template test',
							content: 'placeholder',
						}
					);

					await admin.visitSiteEditor( {
						postId: template.id,
						postType: 'wp_template',
						canvas: 'edit',
					} );
					await expect(
						editor.getCustomHtmlBlockContentLocator( 'placeholder' )
					).toBeVisible();
					await editor.insertBlock( { name: legacyBlockName } );
					await editor.saveSiteEditorEntities( {
						isOnlyCurrentEntityDirty: true,
					} );

					await page.goto( frontendPage );
					const classicProducts = page.locator(
						'.woocommerce-loop-product__title'
					);
					expect( await classicProducts.count() ).toBeGreaterThan(
						0
					);
					const classicProductsNames =
						await classicProducts.allTextContents();

					expect( classicProductsNames ).toEqual(
						productCollectionProductNames
					);
				} );
			} );
		}
	);

	test.describe( 'default query can be modified', () => {
		test( 'default query can be modified', async ( {
			page,
			pageObject,
			editor,
		} ) => {
			await wpCLI(
				'option update woocommerce_default_catalog_orderby price'
			);

			await pageObject.goToEditorTemplate();

			await pageObject.focusProductCollection();

			// Verify the default order matches the option in the database.
			const sidebarSettings = pageObject.locateSidebarSettings();
			const orderBySelect = sidebarSettings.getByRole( 'combobox', {
				name: 'Default sort by',
			} );
			const editorProductTitle = editor.canvas
				.locator( SELECTORS.productTitle )
				.first();

			await expect( orderBySelect ).toHaveValue( 'price' );
			await expect( editorProductTitle ).toHaveText( 'Single' );

			await orderBySelect.selectOption( 'price-desc' );

			await expect( editorProductTitle ).toHaveText( 'Sunglasses' );

			await editor.saveSiteEditorEntities();
			await pageObject.goToProductCatalogFrontend();

			const frontendProductTitle = page
				.locator( SELECTORS.productTitle )
				.first();
			await expect( frontendProductTitle ).toContainText( 'Sunglasses' );

			await wpCLI(
				'option update woocommerce_default_catalog_orderby menu_order'
			);
		} );
	} );
} );
