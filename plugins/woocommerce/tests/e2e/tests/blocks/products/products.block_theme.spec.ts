/**
 * External dependencies
 */
import {
	test,
	expect,
	Admin,
	BlockData,
	BLOCK_THEME_SLUG,
	RequestUtils,
} from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import {
	getProductsNameFromClassicTemplate,
	getProductCollectionQuery,
	getProductsNameFromProductCollection,
	getProductsNameFromProductQuery,
	insertProductsQuery,
} from './utils';

const blockData: BlockData = {
	name: 'core/query',
	slug: '',
	mainClass: '.wc-block-price-filter',
	selectors: {
		frontend: {},
		editor: {},
	},
};

const templates = {
	'archive-product': {
		templateTitle: 'Product Catalog',
		slug: 'archive-product',
		frontendPage: '/shop/',
		legacyBlockName: 'woocommerce/legacy-template',
	},
};

const classicTemplateComparisonRoutes = {
	'archive-product': {
		testTitle:
			'Products block matches the classic template block on the shop page',
		templateTitle: 'Product Catalog',
		slug: 'archive-product',
		frontendPage: '/shop/',
		legacyBlockName: 'woocommerce/legacy-template',
		needsCreation: false,
	},
	'taxonomy-product_cat': {
		testTitle:
			'Products block matches the classic template block on a product category archive',
		templateTitle: 'Products by Category',
		slug: 'taxonomy-product_cat',
		frontendPage: '/product-category/tshirts/',
		legacyBlockName: 'woocommerce/legacy-template',
		needsCreation: true,
	},
	'product-search-results': {
		testTitle:
			'Products block matches the classic template block on product search results',
		templateTitle: 'Product Search Results',
		slug: 'product-search-results',
		frontendPage: '/?s=shirt&post_type=product',
		legacyBlockName: 'woocommerce/legacy-template',
		needsCreation: false,
	},
};

test.describe( `${ blockData.name } Block `, () => {
	test( 'when Inherits Query From Template other options are hidden, show up otherwise', async ( {
		admin,
		editor,
		page,
	} ) => {
		await admin.visitSiteEditor( {
			postId: `${ BLOCK_THEME_SLUG }//archive-product`,
			postType: 'wp_template',
			canvas: 'edit',
		} );
		await editor.setContent( '' );
		await insertProductsQuery( editor );
		const block = await editor.getBlockByName( blockData.name );
		await editor.selectBlocks( block );
		await editor.openDocumentSettingsSidebar();
		const advancedFilterOption = page.getByLabel(
			'Advanced Filters options'
		);
		const inheritQueryFromTemplateOption = page.getByLabel(
			'Inherit query from template'
		);

		await expect( advancedFilterOption ).toBeHidden();
		await expect( inheritQueryFromTemplateOption ).toBeVisible();

		await inheritQueryFromTemplateOption.click();

		await expect( advancedFilterOption ).toBeVisible();
		await expect( inheritQueryFromTemplateOption ).toBeVisible();
	} );

	test( 'product button should add product to the cart when inheriting query from template', async ( {
		admin,
		editor,
		page,
		frontendUtils,
	} ) => {
		await admin.visitSiteEditor( {
			postId: `${ BLOCK_THEME_SLUG }//archive-product`,
			postType: 'wp_template',
			canvas: 'edit',
		} );
		await editor.setContent( '' );
		await insertProductsQuery( editor );
		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );
		await frontendUtils.goToShop();

		const addToCartButton = page.getByRole( 'button', {
			name: 'Add to cart: “Single”',
		} );
		await addToCartButton.click();
		await expect( addToCartButton ).toHaveText( '1 in cart' );
		const cartLink = page.getByRole( 'link', { name: 'View cart' } );
		await expect( cartLink ).toBeVisible();
	} );
} );

for ( const {
	templateTitle,
	slug,
	frontendPage,
	legacyBlockName,
} of Object.values( templates ) ) {
	test.describe( `${ templateTitle } template`, () => {
		test( 'Product Collection matches with classic template block', async ( {
			admin,
			editor,
			page,
		} ) => {
			await admin.visitSiteEditor( {
				postId: `${ BLOCK_THEME_SLUG }//${ slug }`,
				postType: 'wp_template',
				canvas: 'edit',
			} );
			await editor.setContent( '' );
			await insertProductsQuery( editor );
			await page
				.getByRole( 'button', {
					name: 'Upgrade to Product Collection',
				} )
				.click();
			const expectProductCollectionQuery = async () => {
				// The helper returns `{}` until the upgraded block reaches the
				// editor store, and reading it once would dereference that empty
				// object. Poll for the shape first. This is a condition on the
				// block's own attributes, not a wait for the editor to settle.
				await expect
					.poll( () => getProductCollectionQuery( page ) )
					.toMatchObject( {
						isProductCollectionBlock: true,
						inherit: true,
					} );

				const query = await getProductCollectionQuery( page );
				expect( query.perPage ).toBeGreaterThan( 1 );
			};
			await expectProductCollectionQuery();

			await editor.insertBlock( { name: legacyBlockName } );
			await editor.canvas.locator( 'body' ).click();

			await editor.saveSiteEditorEntities( {
				isOnlyCurrentEntityDirty: true,
			} );
			await page.reload();
			await editor.canvas.locator( 'body' ).waitFor();
			await expectProductCollectionQuery();

			await page.goto( frontendPage );

			const classicProducts =
				await getProductsNameFromClassicTemplate( page );
			const productCollectionProducts =
				await getProductsNameFromProductCollection( page );

			expect( classicProducts.length ).toBeGreaterThan( 1 );
			expect( productCollectionProducts.length ).toBeGreaterThan( 1 );
			expect( classicProducts ).toEqual( productCollectionProducts );
		} );
	} );
}

const openRouteTemplateForEditing = async (
	admin: Admin,
	requestUtils: RequestUtils,
	{
		templateTitle,
		slug,
		needsCreation,
	}: { templateTitle: string; slug: string; needsCreation: boolean }
) => {
	if ( needsCreation ) {
		const template = await requestUtils.createTemplate( 'wp_template', {
			slug,
			title: templateTitle,
			content: '',
		} );

		await admin.visitSiteEditor( {
			postId: template.id,
			postType: 'wp_template',
			canvas: 'edit',
		} );
		return;
	}

	await admin.visitSiteEditor( {
		postId: `${ BLOCK_THEME_SLUG }//${ slug }`,
		postType: 'wp_template',
		canvas: 'edit',
	} );
};

for ( const {
	testTitle,
	templateTitle,
	slug,
	frontendPage,
	legacyBlockName,
	needsCreation,
} of Object.values( classicTemplateComparisonRoutes ) ) {
	test.describe( `${ templateTitle } template, Products block`, () => {
		test( testTitle, async ( { admin, editor, page, requestUtils } ) => {
			await openRouteTemplateForEditing( admin, requestUtils, {
				templateTitle,
				slug,
				needsCreation,
			} );
			await editor.setContent( '' );
			await insertProductsQuery( editor );
			await editor.insertBlock( { name: legacyBlockName } );
			await editor.canvas.locator( 'body' ).click();

			await editor.saveSiteEditorEntities( {
				isOnlyCurrentEntityDirty: true,
			} );

			await page.goto( frontendPage );
			await expect(
				page.locator( '.woocommerce-loop-product__title' ).first()
			).toBeVisible();

			const classicProducts =
				await getProductsNameFromClassicTemplate( page );
			const productQueryProducts =
				await getProductsNameFromProductQuery( page );

			expect( classicProducts.length ).toBeGreaterThan( 1 );
			expect( productQueryProducts.length ).toBeGreaterThan( 1 );
			expect( classicProducts ).toEqual( productQueryProducts );
		} );
	} );
}
