/**
 * External dependencies
 */
import { test as base, expect } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { ProductFiltersPage } from './product-filters.page';

const blockData = {
	name: 'woocommerce/product-filters',
	title: 'Product Filters',
	selectors: {
		frontend: {},
		editor: {
			settings: {},
		},
	},
	slug: 'archive-product',
	productPage: '/product/hoodie/',
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

	test( 'should be visible and contain correct inner blocks', async ( {
		editor,
		pageObject,
	} ) => {
		await pageObject.addProductFiltersBlock( { cleanContent: true } );

		const block = editor.canvas.getByLabel(
			'Block: Product Filters (Experimental)'
		);
		await expect( block ).toBeVisible();

		const filtersbBlockHeading = block.getByRole( 'document', {
			name: 'Filters',
		} );
		await expect( filtersbBlockHeading ).toBeVisible();

		const activeHeading = block.getByRole( 'document', {
			name: 'Active',
		} );
		const activeFilterBlock = block.getByLabel(
			'Block: Product Filter: Active'
		);
		await expect( activeHeading ).toBeVisible();
		await expect( activeFilterBlock ).toBeVisible();

		const priceHeading = block.getByRole( 'document', {
			name: 'Price',
		} );
		const priceFilterBlock = block.getByLabel(
			'Block: Product Filter: Price'
		);
		await expect( priceHeading ).toBeVisible();
		await expect( priceFilterBlock ).toBeVisible();

		const statusHeading = block.getByRole( 'document', {
			name: 'Status',
		} );
		const statusFilterBlock = block.getByLabel(
			'Block: Product Filter: Stock'
		);
		await expect( statusHeading ).toBeVisible();
		await expect( statusFilterBlock ).toBeVisible();

		const colorHeading = block.getByText( 'Color', {
			exact: true,
		} );
		const colorFilterBlock = block.getByLabel(
			'Block: Product Filter: Attribute (Experimental)'
		);
		const expectedColorFilterOptions = [
			'Blue',
			'Green',
			'Gray',
			'Red',
			'Yellow',
		];
		const colorFilterOptions = (
			await colorFilterBlock.allInnerTexts()
		 )[ 0 ].split( '\n' );
		await expect( colorHeading ).toBeVisible();
		await expect( colorFilterBlock ).toBeVisible();
		expect( colorFilterOptions ).toEqual(
			expect.arrayContaining( expectedColorFilterOptions )
		);

		const ratingHeading = block.getByRole( 'document', {
			name: 'Rating',
		} );
		const ratingFilterBlock = block.getByLabel(
			'Block: Product Filter: Rating (Experimental)'
		);
		await expect( ratingHeading ).toBeVisible();
		await expect( ratingFilterBlock ).toBeVisible();
	} );

	test( 'should display the correct customization settings', async ( {
		editor,
		pageObject,
	} ) => {
		await pageObject.addProductFiltersBlock( { cleanContent: true } );

		const block = editor.canvas.getByLabel(
			'Block: Product Filters (Experimental)'
		);
		await expect( block ).toBeVisible();

		await editor.openDocumentSettingsSidebar();

		// Color settings
		const colorSettings = editor.page.getByText( 'ColorTextBackground' );
		const colorTextStylesSetting =
			colorSettings.getByLabel( 'Color Text styles' );
		const colorBackgroundStylesSetting = colorSettings.getByLabel(
			'Color Background styles'
		);

		await expect( colorSettings ).toBeVisible();
		await expect( colorTextStylesSetting ).toBeVisible();
		await expect( colorBackgroundStylesSetting ).toBeVisible();

		// Typography settings
		const typographySettings = editor.page.getByText( 'TypographyFont' );
		const typographySizeSetting = typographySettings.getByRole( 'group', {
			name: 'Font size',
		} );

		await expect( typographySettings ).toBeVisible();
		await expect( typographySizeSetting ).toBeVisible();

		// Border settings
		const borderSettings = editor.page.getByRole( 'heading', {
			name: 'Border',
		} );
		await expect( borderSettings ).toBeVisible();
	} );
} );
