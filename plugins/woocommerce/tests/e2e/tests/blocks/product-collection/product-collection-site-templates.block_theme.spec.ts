/**
 * External dependencies
 */
import { test as base, expect, wpCLI } from '@woocommerce/e2e-utils';

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

test.describe( 'Product Collection', () => {
	const templates = [
		{
			templateTitle: 'Product Category (all terms)',
			slug: 'taxonomy-product_cat',
			frontendPage: '/product-category/music/',
			legacyBlockName: 'woocommerce/legacy-template',
		},
		{
			templateTitle: 'Product Tag (all terms)',
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
