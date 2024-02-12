/**
 * External dependencies
 */
import { test as base, expect } from '@woocommerce/e2e-playwright-utils';
import type { Request } from '@playwright/test';

/**
 * Internal dependencies
 */
import ProductCollectionPage, { SELECTORS } from './product-collection.page';

const test = base.extend< { pageObject: ProductCollectionPage } >( {
	pageObject: async (
		{ page, admin, editor, templateApiUtils, editorUtils },
		use
	) => {
		const pageObject = new ProductCollectionPage( {
			page,
			admin,
			editor,
			templateApiUtils,
			editorUtils,
		} );
		await use( pageObject );
	},
} );

test.describe( 'Product Collection', () => {
	test( 'Renders product collection block correctly with 9 items', async ( {
		pageObject,
	} ) => {
		await pageObject.createNewPostAndInsertBlock();
		expect( pageObject.productTemplate ).not.toBeNull();
		await expect( pageObject.products ).toHaveCount( 9 );
		await expect( pageObject.productImages ).toHaveCount( 9 );
		await expect( pageObject.productTitles ).toHaveCount( 9 );
		await expect( pageObject.productPrices ).toHaveCount( 9 );
		await expect( pageObject.addToCartButtons ).toHaveCount( 9 );

		await pageObject.publishAndGoToFrontend();

		expect( pageObject.productTemplate ).not.toBeNull();
		await expect( pageObject.products ).toHaveCount( 9 );
		await expect( pageObject.productImages ).toHaveCount( 9 );
		await expect( pageObject.productTitles ).toHaveCount( 9 );
		await expect( pageObject.productPrices ).toHaveCount( 9 );
		await expect( pageObject.addToCartButtons ).toHaveCount( 9 );
	} );

	test.describe( 'Product Collection Sidebar Settings', () => {
		test.beforeEach( async ( { pageObject } ) => {
			await pageObject.createNewPostAndInsertBlock();
		} );

		test( 'Reflects the correct number of columns according to sidebar settings', async ( {
			pageObject,
		} ) => {
			await pageObject.setNumberOfColumns( 2 );
			await expect(
				await pageObject.productTemplate.getAttribute( 'class' )
			).toContain( 'columns-2' );

			await pageObject.setNumberOfColumns( 4 );
			await expect(
				await pageObject.productTemplate.getAttribute( 'class' )
			).toContain( 'columns-4' );

			await pageObject.publishAndGoToFrontend();

			await expect(
				await pageObject.productTemplate.getAttribute( 'class' )
			).toContain( 'columns-4' );
		} );

		test( 'Order By - sort products by title in descending order correctly', async ( {
			pageObject,
		} ) => {
			await pageObject.setOrderBy( 'title/desc' );
			const allTitles = await pageObject.productTitles.allInnerTexts();
			const expectedTitles = [ ...allTitles ].sort().reverse();

			expect( allTitles ).toStrictEqual( expectedTitles );

			await pageObject.publishAndGoToFrontend();

			const frontendTitles =
				await pageObject.productTitles.allInnerTexts();
			expect(
				frontendTitles.map( ( title ) =>
					title.replace( 'Protected: ', '' )
				)
			).toStrictEqual( expectedTitles );
		} );

		// Products can be filtered based on 'on sale' status.
		test( 'Products can be filtered based on "on sale" status.', async ( {
			pageObject,
		} ) => {
			// On each page we show 9 products.
			await expect( pageObject.products ).toHaveCount( 9 );
			// All products should not be on sale.
			await expect(
				await pageObject.productImages.filter( {
					hasText: 'Product on sale',
				} )
			).not.toHaveCount( 9 );

			await pageObject.setShowOnlyProductsOnSale( {
				onSale: true,
			} );

			// In test data we have only 6 products on sale
			await expect( pageObject.products ).toHaveCount( 6 );

			// Expect all shown products to be on sale.
			await expect(
				await pageObject.productImages.filter( {
					hasText: 'Product on sale',
				} )
			).toHaveCount( await pageObject.productImages.count() );

			await pageObject.publishAndGoToFrontend();
			await expect( pageObject.products ).toHaveCount( 6 );
			await expect(
				await pageObject.productImages.filter( {
					hasText: 'Product on sale',
				} )
			).toHaveCount( await pageObject.productImages.count() );
		} );

		test( 'Products can be filtered based on selection in handpicked products option', async ( {
			pageObject,
		} ) => {
			await pageObject.addFilter( 'Show Hand-picked Products' );

			const filterName = 'Hand-picked Products';
			await pageObject.setFilterComboboxValue( filterName, [ 'Album' ] );
			await expect( pageObject.products ).toHaveCount( 1 );

			const productNames = [ 'Album', 'Cap' ];
			await pageObject.setFilterComboboxValue( filterName, productNames );
			await expect( pageObject.products ).toHaveCount( 2 );
			await expect( pageObject.productTitles ).toHaveText( productNames );

			await pageObject.publishAndGoToFrontend();
			await expect( pageObject.products ).toHaveCount( 2 );
			await expect( pageObject.productTitles ).toHaveText( productNames );
		} );

		test( 'Products can be filtered based on keyword.', async ( {
			pageObject,
		} ) => {
			await pageObject.createNewPostAndInsertBlock();
			await pageObject.addFilter( 'Keyword' );

			await pageObject.setKeyword( 'Album' );
			await expect( pageObject.productTitles ).toHaveText( [ 'Album' ] );

			await pageObject.setKeyword( 'Cap' );
			await expect( pageObject.productTitles ).toHaveText( [ 'Cap' ] );

			await pageObject.publishAndGoToFrontend();
			await expect( pageObject.productTitles ).toHaveText( [ 'Cap' ] );
		} );

		test( 'Products can be filtered based on category.', async ( {
			pageObject,
		} ) => {
			const filterName = 'Product categories';
			await pageObject.addFilter( 'Show Taxonomies' );
			await pageObject.setFilterComboboxValue( filterName, [
				'Clothing',
			] );
			await expect( pageObject.productTitles ).toHaveText( [
				'Logo Collection',
			] );

			await pageObject.setFilterComboboxValue( filterName, [
				'Accessories',
			] );
			const accessoriesProductNames = [
				'Beanie',
				'Beanie with Logo',
				'Belt',
				'Cap',
				'Sunglasses',
			];
			await expect( pageObject.productTitles ).toHaveText(
				accessoriesProductNames
			);

			await pageObject.publishAndGoToFrontend();

			const frontendAccessoriesProductNames = [
				'Beanie',
				'Beanie with Logo',
				'Belt',
				'Cap',
				'Protected: Sunglasses',
			];
			await expect( pageObject.productTitles ).toHaveText(
				frontendAccessoriesProductNames
			);
		} );

		test( 'Products can be filtered based on tags.', async ( {
			pageObject,
		} ) => {
			const filterName = 'Product tags';
			await pageObject.addFilter( 'Show Taxonomies' );
			await pageObject.setFilterComboboxValue( filterName, [
				'Recommended',
			] );
			await expect( pageObject.productTitles ).toHaveText( [ 'Hoodie' ] );

			await pageObject.publishAndGoToFrontend();
			await expect( pageObject.productTitles ).toHaveText( [ 'Hoodie' ] );
		} );

		test( 'Products can be filtered based on product attributes like color, size etc.', async ( {
			pageObject,
		} ) => {
			await pageObject.addFilter( 'Show Product Attributes' );
			await pageObject.setProductAttribute( 'Color', 'Green' );

			await expect( pageObject.products ).toHaveCount( 3 );

			await pageObject.setProductAttribute( 'Size', 'Large' );

			await expect( pageObject.products ).toHaveCount( 1 );

			await pageObject.publishAndGoToFrontend();

			await expect( pageObject.products ).toHaveCount( 1 );
		} );

		test( 'Products can be filtered based on stock status (in stock, out of stock, or backorder).', async ( {
			pageObject,
		} ) => {
			await pageObject.setFilterComboboxValue( 'Stock status', [
				'Out of stock',
			] );

			await expect( pageObject.productTitles ).toHaveText( [
				'T-Shirt with Logo',
			] );

			await pageObject.publishAndGoToFrontend();

			await expect( pageObject.productTitles ).toHaveText( [
				'T-Shirt with Logo',
			] );
		} );

		test( 'Products can be filtered based on featured status.', async ( {
			pageObject,
		} ) => {
			await expect( pageObject.products ).toHaveCount( 9 );

			await pageObject.addFilter( 'Featured' );
			await pageObject.setShowOnlyFeaturedProducts( {
				featured: true,
			} );

			// In test data we have only 4 featured products.
			await expect( pageObject.products ).toHaveCount( 4 );

			await pageObject.publishAndGoToFrontend();

			await expect( pageObject.products ).toHaveCount( 4 );
		} );

		test( 'Products can be filtered based on created date.', async ( {
			pageObject,
		} ) => {
			await expect( pageObject.products ).toHaveCount( 9 );

			await pageObject.addFilter( 'Created' );
			await pageObject.setCreatedFilter( {
				operator: 'within',
				range: 'last3months',
			} );

			// Products are created with the fixed publish date back in 2019
			// so there's no products published in last 3 months.
			await expect( pageObject.products ).toHaveCount( 0 );

			await pageObject.setCreatedFilter( {
				operator: 'before',
				range: 'last3months',
			} );

			await expect( pageObject.products ).toHaveCount( 9 );

			await pageObject.publishAndGoToFrontend();

			await expect( pageObject.products ).toHaveCount( 9 );
		} );

		test( 'Products can be filtered based on price range.', async ( {
			pageObject,
		} ) => {
			await expect( pageObject.products ).toHaveCount( 9 );

			await pageObject.addFilter( 'Price Range' );
			await pageObject.setPriceRange( {
				min: '18.33',
			} );

			await expect( pageObject.products ).toHaveCount( 7 );

			await pageObject.setPriceRange( {
				min: '15.28',
				max: '17.21',
			} );

			await expect( pageObject.products ).toHaveCount( 1 );

			await pageObject.setPriceRange( {
				max: '17.29',
			} );

			await expect( pageObject.products ).toHaveCount( 4 );

			await pageObject.publishAndGoToFrontend();

			await expect( pageObject.products ).toHaveCount( 4 );
		} );

		test.describe( 'Inherit query from template', () => {
			test( 'Inherit query from template should not be visible on posts', async ( {
				pageObject,
			} ) => {
				await pageObject.createNewPostAndInsertBlock();

				const sidebarSettings =
					await pageObject.locateSidebarSettings();
				await expect(
					sidebarSettings.locator(
						SELECTORS.inheritQueryFromTemplateControl
					)
				).toBeHidden();
			} );

			test( 'Inherit query from template should work as expected in Product Catalog template', async ( {
				pageObject,
			} ) => {
				await pageObject.goToProductCatalogAndInsertCollection();

				const sidebarSettings =
					await pageObject.locateSidebarSettings();

				// Inherit query from template should be visible & enabled by default
				await expect(
					sidebarSettings.locator(
						SELECTORS.inheritQueryFromTemplateControl
					)
				).toBeVisible();
				await expect(
					sidebarSettings.locator(
						`${ SELECTORS.inheritQueryFromTemplateControl } input`
					)
				).toBeChecked();

				// "On sale control" should be hidden when inherit query from template is enabled
				await expect(
					sidebarSettings.getByLabel( SELECTORS.onSaleControlLabel )
				).toBeHidden();

				// "On sale control" should be visible when inherit query from template is disabled
				await pageObject.setInheritQueryFromTemplate( false );
				await expect(
					sidebarSettings.getByLabel( SELECTORS.onSaleControlLabel )
				).toBeVisible();

				// "On sale control" should retain its state when inherit query from template is enabled again
				pageObject.setShowOnlyProductsOnSale( {
					onSale: true,
					isLocatorsRefreshNeeded: false,
				} );
				await expect(
					sidebarSettings.getByLabel( SELECTORS.onSaleControlLabel )
				).toBeChecked();
				await pageObject.setInheritQueryFromTemplate( true );
				await expect(
					sidebarSettings.getByLabel( SELECTORS.onSaleControlLabel )
				).toBeHidden();
				await pageObject.setInheritQueryFromTemplate( false );
				await expect(
					sidebarSettings.getByLabel( SELECTORS.onSaleControlLabel )
				).toBeVisible();
				await expect(
					sidebarSettings.getByLabel( SELECTORS.onSaleControlLabel )
				).toBeChecked();
			} );
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

			await expect( await pageObject.products ).toHaveCount( 3 );

			await pageObject.setDisplaySettings( {
				itemsPerPage: 2,
				offset: 0,
				maxPageToShow: 2,
			} );
			await expect( await pageObject.products ).toHaveCount( 2 );

			await pageObject.publishAndGoToFrontend();

			await expect( await pageObject.products ).toHaveCount( 2 );

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

	test.describe( 'Collections', () => {
		test( 'New Arrivals Collection can be added and displays proper products', async ( {
			pageObject,
		} ) => {
			await pageObject.createNewPostAndInsertBlock( 'newArrivals' );

			const newArrivalsProducts = [
				'WordPress Pennant',
				'Logo Collection',
				'Beanie with Logo',
				'T-Shirt with Logo',
				'Single',
			];

			await expect( pageObject.products ).toHaveCount( 5 );
			await expect( pageObject.productTitles ).toHaveText(
				newArrivalsProducts
			);

			await pageObject.publishAndGoToFrontend();

			await expect( pageObject.products ).toHaveCount( 5 );
		} );

		// When creating reviews programmatically the ratings are not propagated
		// properly so products order by rating is undeterministic in test env.
		// eslint-disable-next-line playwright/no-skipped-test
		test.skip( 'Top Rated Collection can be added and displays proper products', async ( {
			pageObject,
		} ) => {
			await pageObject.createNewPostAndInsertBlock( 'topRated' );

			const topRatedProducts = [
				'V Neck T Shirt',
				'Hoodie',
				'Hoodie with Logo',
				'T-Shirt',
				'Beanie',
			];

			await expect( pageObject.products ).toHaveCount( 5 );
			await expect( pageObject.productTitles ).toHaveText(
				topRatedProducts
			);

			await pageObject.publishAndGoToFrontend();

			await expect( pageObject.products ).toHaveCount( 5 );
		} );

		// There's no orders in test env so the order of Best Sellers
		// is undeterministic in test env. Requires further work.
		// eslint-disable-next-line playwright/no-skipped-test
		test.skip( 'Best Sellers Collection can be added and displays proper products', async ( {
			pageObject,
		} ) => {
			await pageObject.createNewPostAndInsertBlock( 'bestSellers' );

			const bestSellersProducts = [
				'Album',
				'Hoodie',
				'Single',
				'Hoodie with Logo',
				'T-Shirt with Logo',
			];

			await expect( pageObject.products ).toHaveCount( 5 );
			await expect( pageObject.productTitles ).toHaveText(
				bestSellersProducts
			);

			await pageObject.publishAndGoToFrontend();

			await expect( pageObject.products ).toHaveCount( 5 );
		} );

		test( 'On Sale Collection can be added and displays proper products', async ( {
			pageObject,
		} ) => {
			await pageObject.createNewPostAndInsertBlock( 'onSale' );

			const onSaleProducts = [
				'Beanie',
				'Beanie with Logo',
				'Belt',
				'Cap',
				'Hoodie',
			];

			await expect( pageObject.products ).toHaveCount( 5 );
			await expect( pageObject.productTitles ).toHaveText(
				onSaleProducts
			);

			await pageObject.publishAndGoToFrontend();

			await expect( pageObject.products ).toHaveCount( 5 );
		} );

		test( 'Featured Collection can be added and displays proper products', async ( {
			pageObject,
		} ) => {
			await pageObject.createNewPostAndInsertBlock( 'featured' );

			const featuredProducts = [
				'Cap',
				'Hoodie with Zipper',
				'Sunglasses',
				'V-Neck T-Shirt',
			];

			await expect( pageObject.products ).toHaveCount( 4 );
			await expect( pageObject.productTitles ).toHaveText(
				featuredProducts
			);

			await pageObject.publishAndGoToFrontend();

			await expect( pageObject.products ).toHaveCount( 4 );
		} );

		test( "Product Catalog Collection can be added in post and doesn't inherit query from template", async ( {
			pageObject,
		} ) => {
			await pageObject.createNewPostAndInsertBlock( 'productCatalog' );

			const sidebarSettings = await pageObject.locateSidebarSettings();
			const input = sidebarSettings.locator(
				`${ SELECTORS.inheritQueryFromTemplateControl } input`
			);

			await expect( input ).toBeHidden();
			await expect( pageObject.products ).toHaveCount( 9 );

			await pageObject.publishAndGoToFrontend();

			await expect( pageObject.products ).toHaveCount( 9 );
		} );

		test( 'Product Catalog Collection can be added in product archive and inherits query from template', async ( {
			pageObject,
		} ) => {
			await pageObject.goToProductCatalogAndInsertCollection(
				'productCatalog'
			);

			const sidebarSettings = await pageObject.locateSidebarSettings();
			const input = sidebarSettings.locator(
				`${ SELECTORS.inheritQueryFromTemplateControl } input`
			);

			await expect( input ).toBeChecked();
		} );

		test.describe( 'Have hidden implementation in UI', () => {
			test( 'New Arrivals', async ( { pageObject } ) => {
				await pageObject.createNewPostAndInsertBlock( 'newArrivals' );
				const input = await pageObject.getOrderByElement();

				await expect( input ).toBeHidden();
			} );

			test( 'Top Rated', async ( { pageObject } ) => {
				await pageObject.createNewPostAndInsertBlock( 'topRated' );
				const input = await pageObject.getOrderByElement();

				await expect( input ).toBeHidden();
			} );

			test( 'Best Sellers', async ( { pageObject } ) => {
				await pageObject.createNewPostAndInsertBlock( 'bestSellers' );
				const input = await pageObject.getOrderByElement();

				await expect( input ).toBeHidden();
			} );

			test( 'On Sale', async ( { pageObject } ) => {
				await pageObject.createNewPostAndInsertBlock( 'onSale' );
				const sidebarSettings =
					await pageObject.locateSidebarSettings();
				const input = sidebarSettings.getByLabel(
					SELECTORS.onSaleControlLabel
				);

				await expect( input ).toBeHidden();
			} );

			test( 'Featured', async ( { pageObject } ) => {
				await pageObject.createNewPostAndInsertBlock( 'featured' );
				const sidebarSettings =
					await pageObject.locateSidebarSettings();
				const input = sidebarSettings.getByLabel(
					SELECTORS.featuredControlLabel
				);

				await expect( input ).toBeHidden();
			} );
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
	} );

	test.describe( 'Location is recognised', () => {
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
				!! searchParams.get( `location[sourceData][productId]` )
			);
		};

		const getLocationDetailsFromRequest = (
			request: Request,
			locationType?: string
		) => {
			const searchParams = new URLSearchParams( request.url() );

			if ( locationType === 'product' ) {
				return {
					type: searchParams.get( 'location[type]' ),
					productId: searchParams.get(
						`location[sourceData][productId]`
					),
				};
			}

			if ( locationType === 'archive' ) {
				return {
					type: searchParams.get( 'location[type]' ),
					taxonomy: searchParams.get(
						`location[sourceData][taxonomy]`
					),
					termId: searchParams.get( `location[sourceData][termId]` ),
				};
			}

			return {
				type: searchParams.get( 'location[type]' ),
				sourceData: searchParams.get( `location[sourceData]` ),
			};
		};

		test( 'as product in specific Single Product template', async ( {
			page,
			pageObject,
			editorUtils,
		} ) => {
			const productName = 'Cap';
			const productSlug = 'cap';

			await editorUtils.openSpecificProductTemplate(
				productName,
				productSlug
			);

			await editorUtils.insertBlockUsingGlobalInserter(
				pageObject.BLOCK_NAME
			);

			const locationReuqestPromise =
				page.waitForRequest( filterProductRequest );
			await pageObject.chooseCollectionInTemplate( 'featured' );
			const locationRequest = await locationReuqestPromise;

			const { type, productId } = getLocationDetailsFromRequest(
				locationRequest,
				'product'
			);

			expect( type ).toBe( 'product' );
			expect( productId ).toBeTruthy();
		} );
		test( 'as category in Products by Category template', async ( {
			admin,
			editorUtils,
			pageObject,
			page,
		} ) => {
			await admin.visitSiteEditor( {
				postId: `woocommerce/woocommerce//taxonomy-product_cat`,
				postType: 'wp_template',
			} );
			await editorUtils.enterEditMode();
			await editorUtils.insertBlockUsingGlobalInserter(
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
			editorUtils,
			pageObject,
			page,
		} ) => {
			await admin.visitSiteEditor( {
				postId: `woocommerce/woocommerce//taxonomy-product_tag`,
				postType: 'wp_template',
			} );
			await editorUtils.enterEditMode();
			await editorUtils.insertBlockUsingGlobalInserter(
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

		test( 'as generic in post', async ( {
			admin,
			editorUtils,
			pageObject,
			page,
		} ) => {
			await admin.createNewPost();
			await editorUtils.insertBlockUsingGlobalInserter(
				pageObject.BLOCK_NAME
			);

			const locationReuqestPromise = page.waitForRequest( filterRequest );
			await pageObject.chooseCollectionInPost( 'featured' );
			const locationRequest = await locationReuqestPromise;
			const { type, sourceData } =
				getLocationDetailsFromRequest( locationRequest );

			expect( type ).toBe( 'generic' );
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
		test( 'Product Catalog: Sends only ID in Query Context', async ( {
			pageObject,
		} ) => {
			const url = await pageObject.setupAndFetchQueryContextURL( {
				collection: 'productCatalog',
			} );

			await expect(
				url.searchParams.has( 'productCollectionQueryContext[id]' )
			).toBeTruthy();

			// There shouldn't be collection in the query context
			// Because Product Catalog isn't a collection
			await expect(
				url.searchParams.has(
					'productCollectionQueryContext[collection]'
				)
			).toBeFalsy();
		} );

		test( 'Collections: collection should be present in query context', async ( {
			pageObject,
		} ) => {
			const url = await pageObject.setupAndFetchQueryContextURL( {
				collection: 'onSale',
			} );

			const collectionName = url.searchParams.get(
				'productCollectionQueryContext[collection]'
			);
			await expect( collectionName ).toBeTruthy();
			await expect( collectionName ).toBe(
				'woocommerce/product-collection/on-sale'
			);
		} );
	} );
} );
