/**
 * External dependencies
 */
import { test as base, expect } from '@woocommerce/e2e-utils';

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
	test.beforeEach( async ( { admin, requestUtils } ) => {
		await requestUtils.activatePlugin(
			'woocommerce-blocks-test-enable-experimental-features'
		);
		await admin.visitSiteEditor( {
			postId: `woocommerce/woocommerce//${ blockData.slug }`,
			postType: 'wp_template',
			canvas: 'edit',
		} );
	} );

	test( 'should display the correct inspector style controls', async ( {
		editor,
		pageObject,
	} ) => {
		await pageObject.addProductFiltersBlock( { cleanContent: true } );

		const block = editor.canvas
			.getByLabel( 'Block: Attribute (Experimental)' )
			.getByLabel( 'Block: Filter Options' );

		await expect( block ).toBeVisible();

		await block.click();
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
		await expect(
			editor.page.getByText( 'DisplayListChips' )
		).toBeVisible();
	} );

	test( 'should display the correct inspector setting controls', async ( {
		editor,
		pageObject,
	} ) => {
		await pageObject.addProductFiltersBlock( { cleanContent: true } );

		const block = editor.canvas
			.getByLabel( 'Block: Attribute (Experimental)' )
			.getByLabel( 'Block: Filter Options' );

		await expect( block ).toBeVisible();

		await editor.openDocumentSettingsSidebar();
		await block.click();

		await expect(
			editor.page.getByLabel( 'Editor settings' ).getByRole( 'button', {
				name: 'Attribute',
				exact: true,
			} )
		).toBeVisible();
		await expect(
			editor.page
				.getByLabel( 'Editor settings' )
				.getByRole( 'button', { name: 'Settings', exact: true } )
		).toBeVisible();
		await expect( editor.page.getByText( 'Sort order' ) ).toBeVisible();
		await expect( editor.page.getByText( 'LogicAnyAll' ) ).toBeVisible();
	} );

	test( 'should dynamically set block title and heading based on the selected attribute', async ( {
		editor,
		pageObject,
	} ) => {
		await pageObject.addProductFiltersBlock( { cleanContent: true } );

		const block = editor.canvas
			.getByLabel( 'Block: Attribute (Experimental)' )
			.getByLabel( 'Block: Filter Options' );

		await editor.openDocumentSettingsSidebar();
		await block.click();

		await editor.page
			.getByRole( 'tabpanel' )
			.getByRole( 'combobox' )
			.first()
			.click();
		await editor.page
			.getByRole( 'option', { name: 'Size', exact: true } )
			.click();

		await pageObject.page.getByLabel( 'Document Overview' ).click();
		const listView = pageObject.page.getByLabel( 'List View' );

		await expect( listView ).toBeVisible();

		const productFilterAttributeSizeBlockListItem = listView.getByText(
			'Size (Experimental)' // it must select the attribute with the highest product count
		);
		await expect( productFilterAttributeSizeBlockListItem ).toBeVisible();

		const productFilterAttributeWrapperBlock = editor.canvas.getByLabel(
			'Block: Attribute (Experimental)'
		);
		await editor.selectBlocks( productFilterAttributeWrapperBlock );
		await expect( productFilterAttributeWrapperBlock ).toBeVisible();

		const productFilterAttributeBlockHeading =
			productFilterAttributeWrapperBlock.getByText( 'Size', {
				exact: true,
			} );

		await expect( productFilterAttributeBlockHeading ).toBeVisible();
	} );
} );
