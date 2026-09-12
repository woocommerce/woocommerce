/**
 * External dependencies
 */
import {
	test,
	expect,
	BlockData,
	BLOCK_THEME_SLUG,
} from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import {
	getProductsNameFromClassicTemplate,
	getProductCollectionQuery,
	getProductsNameFromProductCollection,
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
				const query = await getProductCollectionQuery( page );
				expect( query.isProductCollectionBlock ).toBe( true );
				expect( query.inherit ).toBe( true );
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
