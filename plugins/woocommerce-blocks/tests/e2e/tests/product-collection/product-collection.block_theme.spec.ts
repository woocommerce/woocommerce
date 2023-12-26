/**
 * External dependencies
 */
import { test as base, expect } from '@woocommerce/e2e-playwright-utils';

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
		await pageObject.createNewPostAndInsertBlock();
		await use( pageObject );
	},
} );

test.describe( 'Product Collection', () => {
	test( 'Renders product collection block correctly with 9 items', async ( {
		pageObject,
	} ) => {
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

			expect(
				await pageObject.productTitles.allInnerTexts()
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
			await expect( pageObject.productTitles ).toHaveText(
				accessoriesProductNames
			);
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

		// TODO There are no products with stock status 'Out of stock' in test data.
		// eslint-disable-next-line playwright/no-skipped-test
		test.skip( 'Products can be filtered based on stock status (in stock, out of stock, or backorder).', async ( {
			pageObject,
		} ) => {
			await pageObject.setFilterComboboxValue( 'Stock status', [
				'Out of stock',
			] );

			await expect( pageObject.products ).toHaveCount( 1 );

			await pageObject.publishAndGoToFrontend();

			await expect( pageObject.products ).toHaveCount( 1 );
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
				await pageObject.goToProductCatalogAndInsertBlock();

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
		test( 'Toolbar -> Items per page, offset & max page to show', async ( {
			pageObject,
		} ) => {
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
			await pageObject.createNewPostAndInsertBlock();
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
} );
