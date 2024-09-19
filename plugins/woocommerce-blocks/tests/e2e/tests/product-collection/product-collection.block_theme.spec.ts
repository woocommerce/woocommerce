/**
 * External dependencies
 */
import { Request } from '@playwright/test';
import { test as base, expect } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import ProductCollectionPage, {
	BLOCK_LABELS,
	Collections,
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
	test( 'Renders product collection block correctly with 9 items', async ( {
		pageObject,
	} ) => {
		await pageObject.createNewPostAndInsertBlock();
		await expect( pageObject.products ).toHaveCount( 9 );
		await expect( pageObject.productImages ).toHaveCount( 9 );
		await expect( pageObject.productTitles ).toHaveCount( 9 );
		await expect( pageObject.productPrices ).toHaveCount( 9 );
		await expect( pageObject.addToCartButtons ).toHaveCount( 9 );

		await pageObject.publishAndGoToFrontend();

		await expect( pageObject.products ).toHaveCount( 9 );
		await expect( pageObject.productImages ).toHaveCount( 9 );
		await expect( pageObject.productTitles ).toHaveCount( 9 );
		await expect( pageObject.productPrices ).toHaveCount( 9 );
		await expect( pageObject.addToCartButtons ).toHaveCount( 9 );
	} );

	test( 'Can be migrated to from Products (Beta) block', async ( {
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
			editor.canvas.getByLabel( 'Block: Products (Beta)' )
		).toBeVisible();

		await editor.canvas
			.getByRole( 'button', { name: 'Start blank' } )
			.click();
		await editor.canvas.getByLabel( 'Title & Date' ).click();

		await page
			.getByRole( 'button', { name: 'Upgrade to Product Collection' } )
			.click();

		await expect(
			editor.canvas.getByLabel( 'Block: Products (Beta)' )
		).toBeHidden();
		await expect(
			editor.canvas.getByLabel( 'Block: Product Collection' ).first()
		).toBeVisible();
		await expect(
			page.getByRole( 'button', { name: 'Choose collection' } )
		).toBeVisible();
	} );

	test.describe( 'when no results are found', () => {
		test.beforeEach( async ( { admin } ) => {
			await admin.createNewPost();
		} );

		test.skip( 'does not render', async ( {
			page,
			editor,
			pageObject,
		} ) => {
			await pageObject.insertProductCollection();
			await pageObject.chooseCollectionInPost( 'featured' );
			await pageObject.addFilter( 'Price Range' );
			await pageObject.setPriceRange( {
				max: '1',
			} );

			const featuredBlock = editor.canvas.getByLabel( 'Block: Featured' );

			await expect(
				featuredBlock.getByText( 'Featured products' )
			).toBeVisible();
			// The "No results found" info is rendered in editor for all collections.
			await expect(
				featuredBlock.getByText( 'No results found' )
			).toBeVisible();

			await pageObject.publishAndGoToFrontend();

			const content = page.locator( 'main' );

			await expect( content ).not.toContainText( 'Featured products' );
			await expect( content ).not.toContainText( 'No results found' );
		} );

		// This test ensures the runtime render state is correctly reset for
		// each block.
		test( 'does not prevent subsequent blocks from render', async ( {
			page,
			pageObject,
		} ) => {
			await pageObject.insertProductCollection();
			await pageObject.chooseCollectionInPost( 'featured' );
			await pageObject.addFilter( 'Price Range' );
			await pageObject.setPriceRange( {
				max: '1',
			} );

			await pageObject.insertProductCollection();
			await pageObject.chooseCollectionInPost( 'topRated' );

			await pageObject.refreshLocators( 'editor' );
			await expect( pageObject.products ).toHaveCount( 5 );

			await pageObject.publishAndGoToFrontend();

			await pageObject.refreshLocators( 'frontend' );
			await expect( pageObject.products ).toHaveCount( 5 );
			await expect( page.locator( 'main' ) ).not.toContainText(
				'Featured products'
			);
		} );

		test( 'renders if No Results block is present', async ( {
			page,
			editor,
			pageObject,
		} ) => {
			await pageObject.insertProductCollection();
			await pageObject.chooseCollectionInPost( 'productCatalog' );
			await pageObject.addFilter( 'Price Range' );
			await pageObject.setPriceRange( {
				max: '1',
			} );

			await expect(
				editor.canvas.getByText( 'No results found' )
			).toBeVisible();

			await pageObject.publishAndGoToFrontend();

			await expect( page.getByText( 'No results found' ) ).toBeVisible();
		} );
	} );

	test.describe( 'Renders correctly with all Product Elements', () => {
		const expectedProductContent = [
			'Beanie', // core/post-title
			'$20.00 Original price was: $20.00.$18.00Current price is: $18.00.', // woocommerce/product-price
			'woo-beanie', // woocommerce/product-sku
			'In stock', // woocommerce/product-stock-indicator
			'This is a simple product.', // core/post-excerpt
			'Accessories', // core/post-terms - product_cat
			'Recommended', // core/post-terms - product_tag
			'SaleProduct on sale', // woocommerce/product-sale-badge
			'Add to cart', // woocommerce/product-button
		];

		test( 'In a post', async ( { page, editor, pageObject } ) => {
			await pageObject.createNewPostAndInsertBlock();

			await expect(
				editor.canvas.locator( '[data-testid="product-image"]:visible' )
			).toHaveCount( 9 );

			await pageObject.insertProductElements();
			await pageObject.publishAndGoToFrontend();

			for ( const content of expectedProductContent ) {
				await expect(
					page.locator( '.wc-block-product-template' )
				).toContainText( content );
			}
		} );

		test( 'In a Product Archive (Product Catalog)', async ( {
			page,
			editor,
			pageObject,
		} ) => {
			await pageObject.goToEditorTemplate();

			await expect(
				editor.canvas.locator( '[data-testid="product-image"]:visible' )
			).toHaveCount( 16 );

			await pageObject.insertProductElements();
			await editor.saveSiteEditorEntities( {
				isOnlyCurrentEntityDirty: true,
			} );
			await pageObject.goToProductCatalogFrontend();

			// Workaround for the issue with the product change not being
			// reflected in the frontend yet.
			try {
				await page.getByText( 'woo-beanie' ).waitFor();
			} catch ( _error ) {
				await page.reload();
			}

			for ( const content of expectedProductContent ) {
				await expect(
					page.locator( '.wc-block-product-template' )
				).toContainText( content );
			}
		} );

		test( 'On a Home Page', async ( { page, editor, pageObject } ) => {
			await pageObject.goToHomePageAndInsertCollection();

			await expect(
				editor.canvas.locator( '[data-testid="product-image"]:visible' )
			).toHaveCount( 9 );

			await pageObject.insertProductElements();
			await editor.saveSiteEditorEntities( {
				isOnlyCurrentEntityDirty: true,
			} );
			await pageObject.goToHomePageFrontend();

			for ( const content of expectedProductContent ) {
				await expect(
					page.locator( '.wc-block-product-template' )
				).toContainText( content );
			}
		} );
	} );

	test.describe( 'Toolbar settings', () => {
		test.beforeEach( async ( { pageObject } ) => {
			await pageObject.createNewPostAndInsertBlock();
		} );

		test( 'Toolbar -> Items per page, offset & max page to show', async ( {
			pageObject,
		} ) => {
			await pageObject.clickDisplaySettings();
			await pageObject.setDisplaySettings( {
				itemsPerPage: 3,
				offset: 0,
				maxPageToShow: 2,
			} );

			await expect( pageObject.products ).toHaveCount( 3 );

			await pageObject.setDisplaySettings( {
				itemsPerPage: 2,
				offset: 0,
				maxPageToShow: 2,
			} );
			await expect( pageObject.products ).toHaveCount( 2 );

			await pageObject.publishAndGoToFrontend();

			await expect( pageObject.products ).toHaveCount( 2 );

			const paginationNumbers =
				pageObject.pagination.locator( '.page-numbers' );
			await expect( paginationNumbers ).toHaveCount( 2 );
		} );
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
			// By default there should be 3 columns, so grid-template-columns
			// should be compiled to three values
			await expect( productTemplate ).toHaveCSS(
				'grid-template-columns',
				/^\d+(\.\d+)?px \d+(\.\d+)?px \d+(\.\d+)?px$/
			);

			await pageObject.setViewportSize( {
				height: 667,
				width: 390, // iPhone 12 Pro
			} );

			// Verifies grid-template-columns compiles to two numbers,
			// which means there are two columns on mobile.
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

			// In the original viewport size, we expect the product width to be less than the parent width
			// because we will have more than 1 column
			let productSize = await firstProduct.boundingBox();
			let parentSize = await firstProduct
				.locator( 'xpath=..' )
				.boundingBox();
			expect( productSize?.width ).toBeLessThan(
				parentSize?.width as number
			);

			await pageObject.setViewportSize( {
				height: 667,
				width: 390, // iPhone 12 Pro
			} );

			// In the smaller viewport size, we expect the product width to be (approximately) the same as the parent width
			// because we will have only 1 column
			productSize = await firstProduct.boundingBox();
			parentSize = await firstProduct.locator( 'xpath=..' ).boundingBox();
			expect( productSize?.width ).toBeCloseTo(
				parentSize?.width as number
			);
		} );
	} );

	test.describe( 'With other blocks', () => {
		test( 'In Single Product block', async ( { admin, pageObject } ) => {
			await admin.createNewPost();
			await pageObject.insertProductCollectionInSingleProductBlock();
			await pageObject.chooseCollectionInPost( 'featured' );
			await pageObject.refreshLocators( 'editor' );

			const featuredProducts = [
				'Cap',
				'Hoodie with Zipper',
				'Sunglasses',
				'V-Neck T-Shirt',
			];
			const featuredProductsPrices = [
				'Previous price:$18.00Discounted price:$16.00',
				'$45.00',
				'$90.00',
				'Price between $15.00 and $20.00$15.00 — $20.00',
			];

			await expect( pageObject.products ).toHaveCount( 4 );
			// This verifies if Core's block context is provided
			await expect( pageObject.productTitles ).toHaveText(
				featuredProducts
			);
			// This verifies if Blocks's product context is provided
			await expect( pageObject.productPrices ).toHaveText(
				featuredProductsPrices
			);
		} );

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

	test.describe( 'Location is recognized', () => {
		const filterRequest = ( request: Request ) => {
			const url = request.url();
			return (
				url.includes( 'wp/v2/product' ) &&
				url.includes( 'isProductCollectionBlock=true' )
			);
		};

		const filterProductRequest = ( request: Request ) => {
			const url = request.url();
			const searchParams = new URLSearchParams( request.url() );

			return (
				url.includes( 'wp/v2/product' ) &&
				searchParams.get( 'isProductCollectionBlock' ) === 'true' &&
				!! searchParams.get(
					`productCollectionLocation[sourceData][productId]`
				)
			);
		};

		const getLocationDetailsFromRequest = (
			request: Request,
			locationType?: string
		) => {
			const searchParams = new URLSearchParams( request.url() );

			if ( locationType === 'product' ) {
				return {
					type: searchParams.get( 'productCollectionLocation[type]' ),
					productId: searchParams.get(
						`productCollectionLocation[sourceData][productId]`
					),
				};
			}

			if ( locationType === 'archive' ) {
				return {
					type: searchParams.get( 'productCollectionLocation[type]' ),
					taxonomy: searchParams.get(
						`productCollectionLocation[sourceData][taxonomy]`
					),
					termId: searchParams.get(
						`productCollectionLocation[sourceData][termId]`
					),
				};
			}

			return {
				type: searchParams.get( 'productCollectionLocation[type]' ),
				sourceData: searchParams.get(
					`productCollectionLocation[sourceData]`
				),
			};
		};

		test( 'as product in specific Single Product template', async ( {
			admin,
			page,
			pageObject,
			editor,
		} ) => {
			await admin.visitSiteEditor( { path: '/wp_template' } );

			await page
				.getByRole( 'button', { name: 'Add New Template' } )
				.click();
			await page
				.getByRole( 'button', { name: 'Single Item: Product' } )
				.click();
			await page
				.getByRole( 'option', {
					name: `Cap http://localhost:8889/product/cap/`,
				} )
				.click();
			await page
				.getByRole( 'button', {
					name: 'Skip',
				} )
				.click();

			await editor.insertBlockUsingGlobalInserter(
				pageObject.BLOCK_NAME
			);

			const locationRequestPromise =
				page.waitForRequest( filterProductRequest );
			await pageObject.chooseCollectionInTemplate( 'featured' );
			const locationRequest = await locationRequestPromise;

			const { type, productId } = getLocationDetailsFromRequest(
				locationRequest,
				'product'
			);

			expect( type ).toBe( 'product' );
			expect( productId ).toBeTruthy();
		} );
		test( 'as category in Products by Category template', async ( {
			admin,
			editor,
			pageObject,
			page,
		} ) => {
			await admin.visitSiteEditor( {
				postId: `woocommerce/woocommerce//taxonomy-product_cat`,
				postType: 'wp_template',
				canvas: 'edit',
			} );
			await editor.insertBlockUsingGlobalInserter(
				pageObject.BLOCK_NAME
			);

			const locationReuqestPromise = page.waitForRequest( filterRequest );
			await pageObject.chooseCollectionInTemplate( 'featured' );
			const locationRequest = await locationReuqestPromise;
			const { type, taxonomy, termId } = getLocationDetailsFromRequest(
				locationRequest,
				'archive'
			);

			expect( type ).toBe( 'archive' );
			expect( taxonomy ).toBe( 'product_cat' );
			// Field is sent as a null but browser converts it to empty string
			expect( termId ).toBe( '' );
		} );

		test( 'as tag in Products by Tag template', async ( {
			admin,
			editor,
			pageObject,
			page,
		} ) => {
			await admin.visitSiteEditor( {
				postId: `woocommerce/woocommerce//taxonomy-product_tag`,
				postType: 'wp_template',
				canvas: 'edit',
			} );
			await editor.insertBlockUsingGlobalInserter(
				pageObject.BLOCK_NAME
			);

			const locationReuqestPromise = page.waitForRequest( filterRequest );
			await pageObject.chooseCollectionInTemplate( 'featured' );
			const locationRequest = await locationReuqestPromise;
			const { type, taxonomy, termId } = getLocationDetailsFromRequest(
				locationRequest,
				'archive'
			);

			expect( type ).toBe( 'archive' );
			expect( taxonomy ).toBe( 'product_tag' );
			// Field is sent as a null but browser converts it to empty string
			expect( termId ).toBe( '' );
		} );

		test( 'as site in post', async ( {
			admin,
			editor,
			pageObject,
			page,
		} ) => {
			await admin.createNewPost();
			await editor.insertBlockUsingGlobalInserter(
				pageObject.BLOCK_NAME
			);

			const locationReuqestPromise = page.waitForRequest( filterRequest );
			await pageObject.chooseCollectionInPost( 'featured' );
			const locationRequest = await locationReuqestPromise;
			const { type, sourceData } =
				getLocationDetailsFromRequest( locationRequest );

			expect( type ).toBe( 'site' );
			// Field is not sent at all. URLSearchParams get method returns a null
			// if field is not available.
			expect( sourceData ).toBe( null );
		} );

		test( 'as product in Single Product block in post', async ( {
			admin,
			pageObject,
			page,
		} ) => {
			await admin.createNewPost();
			await pageObject.insertProductCollectionInSingleProductBlock();
			const locationReuqestPromise =
				page.waitForRequest( filterProductRequest );
			await pageObject.chooseCollectionInPost( 'featured' );
			const locationRequest = await locationReuqestPromise;
			const { type, productId } = getLocationDetailsFromRequest(
				locationRequest,
				'product'
			);

			expect( type ).toBe( 'product' );
			expect( productId ).toBeTruthy();
		} );
	} );

	test.describe( 'Query Context in Editor', () => {
		test( 'Collections: collection should be present in query context', async ( {
			pageObject,
		} ) => {
			const url = await pageObject.setupAndFetchQueryContextURL( {
				collection: 'onSale',
			} );

			const collectionName = url.searchParams.get(
				'productCollectionQueryContext[collection]'
			);
			expect( collectionName ).toBeTruthy();
			expect( collectionName ).toBe(
				'woocommerce/product-collection/on-sale'
			);
		} );
	} );

	test.describe( 'Preview mode in generic archive templates', () => {
		const genericArchiveTemplates = [
			{
				name: 'Products by Tag',
				path: 'woocommerce/woocommerce//taxonomy-product_tag',
			},
			{
				name: 'Products by Category',
				path: 'woocommerce/woocommerce//taxonomy-product_cat',
			},
			{
				name: 'Products by Attribute',
				path: 'woocommerce/woocommerce//taxonomy-product_attribute',
			},
		];

		genericArchiveTemplates.forEach( ( { name, path } ) => {
			test( `${ name } template`, async ( { editor, pageObject } ) => {
				await pageObject.goToEditorTemplate( path );
				await pageObject.focusProductCollection();

				const previewButtonLocator = editor.canvas.getByTestId(
					SELECTORS.previewButtonTestID
				);

				// The preview button should be visible
				await expect( previewButtonLocator ).toBeVisible();

				// The preview button should be hidden when the block is not selected.
				// Changing focus.
				const otherBlockSelector = editor.canvas.getByLabel(
					'Block: Archive Title'
				);
				await editor.selectBlocks( otherBlockSelector );
				await expect( previewButtonLocator ).toBeHidden();

				// Preview button should be visible again when the block is selected.
				await pageObject.focusProductCollection();
				await expect( previewButtonLocator ).toBeVisible();
			} );
		} );
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

		test( 'On Sale collection should be visible after Refresh', async ( {
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

			// Refresh the template and verify "On Sale" collection is still visible
			await editor.saveSiteEditorEntities( {
				isOnlyCurrentEntityDirty: true,
			} );
			await page.reload();
			await expect( productTemplate ).toHaveCount( 2 );
		} );

		test( 'On Sale collection should be visible after Refresh in a Post', async ( {
			page,
			pageObject,
			editor,
		} ) => {
			await pageObject.createNewPostAndInsertBlock( 'onSale' );
			await expect( pageObject.productTemplate ).toBeVisible();

			// Refresh the post and verify "On Sale" collection is still visible
			await editor.saveDraft();
			await page.reload();
			await expect( pageObject.productTemplate ).toBeVisible();
		} );
	} );

	const templates = {
		// This test is disabled because archives are disabled for attributes by default. This can be uncommented when this is toggled on.
		//'taxonomy-product_attribute': {
		//	templateTitle: 'Product Attribute',
		//	slug: 'taxonomy-product_attribute',
		//	frontendPage: '/product-attribute/color/',
		//	legacyBlockName: 'woocommerce/legacy-template',
		//},
		'taxonomy-product_cat': {
			templateTitle: 'Product Category',
			slug: 'taxonomy-product_cat',
			frontendPage: '/product-category/music/',
			legacyBlockName: 'woocommerce/legacy-template',
			expectedProductsCount: 2,
		},
		'taxonomy-product_tag': {
			templateTitle: 'Product Tag',
			slug: 'taxonomy-product_tag',
			frontendPage: '/product-tag/recommended/',
			legacyBlockName: 'woocommerce/legacy-template',
			expectedProductsCount: 2,
		},
		'archive-product': {
			templateTitle: 'Product Catalog',
			slug: 'archive-product',
			frontendPage: '/shop/',
			legacyBlockName: 'woocommerce/legacy-template',
			expectedProductsCount: 16,
		},
		'product-search-results': {
			templateTitle: 'Product Search Results',
			slug: 'product-search-results',
			frontendPage: '/?s=shirt&post_type=product',
			legacyBlockName: 'woocommerce/legacy-template',
			expectedProductsCount: 3,
		},
	};

	for ( const {
		templateTitle,
		slug,
		frontendPage,
		legacyBlockName,
		expectedProductsCount,
	} of Object.values( templates ) ) {
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

				const template = await requestUtils.createTemplate(
					'wp_template',
					{
						slug,
						title: 'classic template test',
						content: 'howdy',
					}
				);

				await admin.visitSiteEditor( {
					postId: template.id,
					postType: 'wp_template',
					canvas: 'edit',
				} );

				await expect(
					editor.canvas.getByText( 'howdy' )
				).toBeVisible();

				await editor.insertBlock( { name: legacyBlockName } );

				await editor.saveSiteEditorEntities( {
					isOnlyCurrentEntityDirty: true,
				} );

				await page.goto( frontendPage );

				const classicProducts = page.locator(
					'.woocommerce-loop-product__title'
				);

				await expect( classicProducts ).toHaveCount(
					expectedProductsCount
				);

				const classicProductsNames =
					await classicProducts.allTextContents();

				expect( classicProductsNames ).toEqual(
					productCollectionProductNames
				);
			} );
		} );
	}
	test.describe( 'Editor: In taxonomies templates', () => {
		test( 'Products by specific category template displays products from this category', async ( {
			admin,
			page,
			editor,
		} ) => {
			const expectedProducts = [
				'Hoodie',
				'Hoodie with Logo',
				'Hoodie with Zipper',
			];

			await admin.visitSiteEditor( { path: '/wp_template' } );

			await page
				.getByRole( 'button', { name: 'Add New Template' } )
				.click();
			await page
				.getByRole( 'button', { name: 'Products by Category' } )
				.click();
			await page
				.getByRole( 'option', {
					name: `Hoodies`,
				} )
				.click();
			await page
				.getByRole( 'option', { name: 'Fallback content' } )
				.click();

			const products = editor.canvas.getByLabel( 'Block: Title' );

			await expect( products ).toHaveText( expectedProducts );
		} );
		test( 'Products by specific tag template displays products from this tag', async ( {
			admin,
			page,
			editor,
		} ) => {
			const expectedProducts = [ 'Beanie', 'Hoodie' ];

			await admin.visitSiteEditor( { path: '/wp_template' } );

			await page
				.getByRole( 'button', { name: 'Add New Template' } )
				.click();
			await page
				.getByRole( 'button', { name: 'Products by Tag' } )
				.click();
			await page
				.getByRole( 'option', {
					name: `Recommended`,
				} )
				.click();
			await page
				.getByRole( 'option', { name: 'Fallback content' } )
				.click();

			const products = editor.canvas.getByLabel( 'Block: Title' );

			await expect( products ).toHaveText( expectedProducts );
		} );
	} );
} );

test.describe( 'Testing "usesReference" argument in "registerProductCollection"', () => {
	const MY_REGISTERED_COLLECTIONS = {
		myCustomCollectionWithProductContext: {
			name: 'My Custom Collection - Product Context',
			label: 'Block: My Custom Collection - Product Context',
			previewLabelTemplate: [ 'woocommerce/woocommerce//single-product' ],
			shouldShowProductPicker: true,
		},
		myCustomCollectionWithCartContext: {
			name: 'My Custom Collection - Cart Context',
			label: 'Block: My Custom Collection - Cart Context',
			previewLabelTemplate: [ 'woocommerce/woocommerce//page-cart' ],
			shouldShowProductPicker: false,
		},
		myCustomCollectionWithOrderContext: {
			name: 'My Custom Collection - Order Context',
			label: 'Block: My Custom Collection - Order Context',
			previewLabelTemplate: [
				'woocommerce/woocommerce//order-confirmation',
			],
			shouldShowProductPicker: false,
		},
		myCustomCollectionWithArchiveContext: {
			name: 'My Custom Collection - Archive Context',
			label: 'Block: My Custom Collection - Archive Context',
			previewLabelTemplate: [
				'woocommerce/woocommerce//taxonomy-product_cat',
			],
			shouldShowProductPicker: false,
		},
		myCustomCollectionMultipleContexts: {
			name: 'My Custom Collection - Multiple Contexts',
			label: 'Block: My Custom Collection - Multiple Contexts',
			previewLabelTemplate: [
				'woocommerce/woocommerce//single-product',
				'woocommerce/woocommerce//order-confirmation',
			],
			shouldShowProductPicker: true,
		},
	};

	// Activate plugin which registers custom product collections
	test.beforeEach( async ( { requestUtils } ) => {
		await requestUtils.activatePlugin(
			'register-product-collection-tester'
		);
	} );

	Object.entries( MY_REGISTERED_COLLECTIONS ).forEach(
		( [ key, collection ] ) => {
			for ( const template of collection.previewLabelTemplate ) {
				test( `Collection "${ collection.name }" should show preview label in "${ template }"`, async ( {
					pageObject,
					editor,
				} ) => {
					await pageObject.goToEditorTemplate( template );
					await pageObject.insertProductCollection();
					await pageObject.chooseCollectionInTemplate(
						key as Collections
					);

					const block = editor.canvas.getByLabel( collection.label );
					const previewButtonLocator = block.getByTestId(
						SELECTORS.previewButtonTestID
					);

					await expect( previewButtonLocator ).toBeVisible();
				} );
			}

			test( `Collection "${ collection.name }" should not show preview label in a post`, async ( {
				pageObject,
				editor,
				admin,
			} ) => {
				await admin.createNewPost();
				await pageObject.insertProductCollection();
				await pageObject.chooseCollectionInPost( key as Collections );

				// Check visibility of product picker
				const editorProductPicker = editor.canvas.locator(
					SELECTORS.productPicker
				);
				const expectedVisibility = collection.shouldShowProductPicker
					? 'toBeVisible'
					: 'toBeHidden';
				await expect( editorProductPicker )[ expectedVisibility ]();

				if ( collection.shouldShowProductPicker ) {
					await pageObject.chooseProductInEditorProductPickerIfAvailable(
						editor.canvas
					);
				}

				// At this point, the product picker should be hidden
				await expect( editorProductPicker ).toBeHidden();

				// Check visibility of preview label
				const block = editor.canvas.getByLabel( collection.label );
				const previewButtonLocator = block.getByTestId(
					SELECTORS.previewButtonTestID
				);

				await expect( previewButtonLocator ).toBeHidden();
			} );

			test( `Collection "${ collection.name }" should not show preview label in Product Catalog template`, async ( {
				pageObject,
				editor,
			} ) => {
				await pageObject.goToProductCatalogAndInsertCollection(
					key as Collections
				);

				const block = editor.canvas.getByLabel( collection.label );
				const previewButtonLocator = block.getByTestId(
					SELECTORS.previewButtonTestID
				);

				await expect( previewButtonLocator ).toBeHidden();
			} );
		}
	);
} );

test.describe( 'Product picker', () => {
	const MY_REGISTERED_COLLECTIONS_THAT_NEEDS_PRODUCT = {
		myCustomCollectionWithProductContext: {
			name: 'My Custom Collection - Product Context',
			label: 'Block: My Custom Collection - Product Context',
			collection:
				'woocommerce/product-collection/my-custom-collection-product-context',
		},
		myCustomCollectionMultipleContexts: {
			name: 'My Custom Collection - Multiple Contexts',
			label: 'Block: My Custom Collection - Multiple Contexts',
			collection:
				'woocommerce/product-collection/my-custom-collection-multiple-contexts',
		},
	};

	// Activate plugin which registers custom product collections
	test.beforeEach( async ( { requestUtils } ) => {
		await requestUtils.activatePlugin(
			'register-product-collection-tester'
		);
	} );

	Object.entries( MY_REGISTERED_COLLECTIONS_THAT_NEEDS_PRODUCT ).forEach(
		( [ key, collection ] ) => {
			test( `For collection "${ collection.name }" - manually selected product reference should be available on Frontend in a post`, async ( {
				pageObject,
				admin,
				page,
				editor,
			} ) => {
				await admin.createNewPost();
				await pageObject.insertProductCollection();
				await pageObject.chooseCollectionInPost( key as Collections );

				// Verify that product picker is shown in Editor
				const editorProductPicker = editor.canvas.locator(
					SELECTORS.productPicker
				);
				await expect( editorProductPicker ).toBeVisible();

				// Once a product is selected, the product picker should be hidden
				await pageObject.chooseProductInEditorProductPickerIfAvailable(
					editor.canvas
				);
				await expect( editorProductPicker ).toBeHidden();

				// On Frontend, verify that product reference is a number
				await pageObject.publishAndGoToFrontend();
				const collectionWithProductContext = page.locator(
					`[data-collection="${ collection.collection }"]`
				);
				const queryAttribute = JSON.parse(
					( await collectionWithProductContext.getAttribute(
						'data-query'
					) ) || '{}'
				);
				expect( typeof queryAttribute?.productReference ).toBe(
					'number'
				);
			} );

			test( `For collection "${ collection.name }" - changing product using inspector control`, async ( {
				pageObject,
				admin,
				page,
				editor,
			} ) => {
				await admin.createNewPost();
				await pageObject.insertProductCollection();
				await pageObject.chooseCollectionInPost( key as Collections );

				// Verify that product picker is shown in Editor
				const editorProductPicker = editor.canvas.locator(
					SELECTORS.productPicker
				);
				await expect( editorProductPicker ).toBeVisible();

				// Once a product is selected, the product picker should be hidden
				await pageObject.chooseProductInEditorProductPickerIfAvailable(
					editor.canvas
				);
				await expect( editorProductPicker ).toBeHidden();

				// Verify that Album is selected
				await expect(
					admin.page.locator( SELECTORS.linkedProductControl.button )
				).toContainText( 'Album' );

				// Change product using inspector control to Beanie
				await admin.page
					.locator( SELECTORS.linkedProductControl.button )
					.click();
				await admin.page
					.locator( SELECTORS.linkedProductControl.popoverContent )
					.getByLabel( 'Beanie', { exact: true } )
					.click();
				await expect(
					admin.page.locator( SELECTORS.linkedProductControl.button )
				).toContainText( 'Beanie' );

				// On Frontend, verify that product reference is a number
				await pageObject.publishAndGoToFrontend();
				const collectionWithProductContext = page.locator(
					`[data-collection="${ collection.collection }"]`
				);
				const queryAttribute = JSON.parse(
					( await collectionWithProductContext.getAttribute(
						'data-query'
					) ) || '{}'
				);
				expect( typeof queryAttribute?.productReference ).toBe(
					'number'
				);
			} );

			test( `For collection "${ collection.name }" - product picker shouldn't be shown in Single Product template`, async ( {
				pageObject,
				admin,
				editor,
			} ) => {
				await admin.visitSiteEditor( {
					postId: `woocommerce/woocommerce//single-product`,
					postType: 'wp_template',
					canvas: 'edit',
				} );
				await editor.canvas.locator( 'body' ).click();
				await pageObject.insertProductCollection();
				await pageObject.chooseCollectionInTemplate(
					key as Collections
				);

				const editorProductPicker = editor.canvas.locator(
					SELECTORS.productPicker
				);
				await expect( editorProductPicker ).toBeHidden();
			} );
		}
	);

	test( 'Product picker should work as expected while changing collection using "Choose collection" button from Toolbar', async ( {
		pageObject,
		admin,
		editor,
	} ) => {
		await admin.createNewPost();
		await pageObject.insertProductCollection();
		await pageObject.chooseCollectionInPost(
			'myCustomCollectionWithProductContext'
		);

		// Verify that product picker is shown in Editor
		const editorProductPicker = editor.canvas.locator(
			SELECTORS.productPicker
		);
		await expect( editorProductPicker ).toBeVisible();

		// Once a product is selected, the product picker should be hidden
		await pageObject.chooseProductInEditorProductPickerIfAvailable(
			editor.canvas
		);
		await expect( editorProductPicker ).toBeHidden();

		// Change collection using Toolbar
		await pageObject.changeCollectionUsingToolbar(
			'myCustomCollectionMultipleContexts'
		);
		await expect( editorProductPicker ).toBeVisible();

		// Once a product is selected, the product picker should be hidden
		await pageObject.chooseProductInEditorProductPickerIfAvailable(
			editor.canvas
		);
		await expect( editorProductPicker ).toBeHidden();

		// Product picker should be hidden for collections that don't need product
		await pageObject.changeCollectionUsingToolbar( 'featured' );
		await expect( editorProductPicker ).toBeHidden();
	} );
} );
