/**
 * External dependencies
 */
import { test as base, expect, BLOCK_THEME_SLUG } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { ProductFiltersPage } from './product-filters.page';

const blockData = {
	name: 'woocommerce/product-filter-attribute',
	selectors: {
		frontend: {},
		editor: {
			settings: {},
		},
	},
	slug: 'archive-product',
};

const test = base.extend< { pageObject: ProductFiltersPage } >( {
	pageObject: async ( { page, editor, frontendUtils }, use ) => {
		const pageObject = new ProductFiltersPage( {
			page,
			editor,
			frontendUtils,
		} );
		await use( pageObject );
	},
} );

test.describe( `${ blockData.name }`, () => {
	test.beforeEach( async ( { admin } ) => {
		await admin.visitSiteEditor( {
			postId: `${ BLOCK_THEME_SLUG }//${ blockData.slug }`,
			postType: 'wp_template',
			canvas: 'edit',
		} );
	} );

	test( 'should display the correct inspector style controls', async ( {
		editor,
		pageObject,
	} ) => {
		await pageObject.addProductFiltersBlock( { cleanContent: true } );

		const block = editor.canvas.getByLabel( 'Block: Color' );

		await expect( block ).toBeVisible();

		await editor.selectBlocks( block );
		await editor.openDocumentSettingsSidebar();
		await editor.page.getByRole( 'tab', { name: 'Styles' } ).click();

		await expect(
			editor.page.getByText( 'ColorAll options are currently hidden' )
		).toBeVisible();
		await expect(
			editor.page.getByText(
				'TypographyAll options are currently hidden'
			)
		).toBeVisible();
		await expect(
			editor.page.getByText(
				'DimensionsAll options are currently hidden'
			)
		).toBeVisible();
	} );
} );
