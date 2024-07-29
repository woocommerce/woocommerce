/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-utils';

const blockData = {
	name: 'woocommerce/product-filters-overlay',
	title: 'Product Filters Overlay (Experimental)',
	selectors: {
		frontend: {},
		editor: {
			settings: {},
		},
	},
};

test.describe( 'Filters Overlay Template Part', () => {
	test.beforeEach( async ( { admin, requestUtils } ) => {
		await requestUtils.activatePlugin(
			'woocommerce-blocks-test-enable-experimental-features'
		);
		await admin.visitSiteEditor( {
			postType: 'wp_template_part',
			postId: 'woocommerce/woocommerce//product-filters-overlay',
			canvas: 'edit',
		} );
	} );

	test( 'should be visible in the template parts list', async ( {
		page,
		admin,
	} ) => {
		await admin.visitSiteEditor( {
			postType: 'wp_template_part',
		} );
		const block = page
			.getByLabel( 'Patterns content' )
			.getByText( 'Filters Overlay' )
			.and( page.getByRole( 'button' ) );
		await expect( block ).toBeVisible();
	} );

	test( 'should render the correct inner blocks', async ( { editor } ) => {
		const navigationBlock = editor.canvas.getByLabel(
			'Block: Navigation (Experimental)'
		);
		const productFiltersTemplatePart = editor.canvas
			.locator( '[data-type="core/template-part"]' )
			.filter( {
				has: editor.canvas.getByLabel(
					'Block: Product Filters (Experimental)'
				),
			} );

		await expect( navigationBlock ).toBeVisible();
		await expect( productFiltersTemplatePart ).toBeVisible();
	} );

	test( 'should navigate to product filters template part', async ( {
		editor,
		page,
	} ) => {
		const block = editor.canvas.getByLabel( `Block: ${ blockData.title }` );
		await block.click();

		await editor.openDocumentSettingsSidebar();

		await expect(
			editor.page.getByText( 'Edit product filters' )
		).toBeVisible();

		await editor.page.getByText( 'Edit product filters' ).click();

		const expectedParts = [
			'postId=woocommerce%2Fwoocommerce%2F%2Fproduct-filters',
			'postType=wp_template_part',
			'canvas=edit',
		];

		for ( const part of expectedParts ) {
			await expect( page ).toHaveURL( new RegExp( `.*${ part }.*` ) );
		}
	} );
} );
