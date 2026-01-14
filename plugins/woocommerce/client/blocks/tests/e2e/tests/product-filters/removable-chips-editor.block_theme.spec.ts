/**
 * External dependencies
 */
import { test as base, expect, BLOCK_THEME_SLUG } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { ProductFiltersPage } from './product-filters.page';

const blockData = {
	name: 'woocommerce/product-filter-active',
	selectors: {
		frontend: {},
		editor: {
			settings: {},
			label: 'Block: Active',
			innerBlocks: {
				chips: {
					label: 'Block: Chips',
				},
			},
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

	test( 'should display the correct inspector layout controls', async ( {
		editor,
		pageObject,
	} ) => {
		await pageObject.addProductFiltersBlock( { cleanContent: true } );

		const activeBlock = editor.canvas.getByLabel(
			blockData.selectors.editor.label
		);

		await expect( activeBlock ).toBeVisible();

		await activeBlock.click();

		const chipsBlock = editor.canvas.getByLabel(
			blockData.selectors.editor.innerBlocks.chips.label
		);

		await expect( chipsBlock ).toBeVisible();
		await editor.selectBlocks( chipsBlock );

		await editor.openDocumentSettingsSidebar();

		await expect( editor.page.getByText( 'Justification' ) ).toBeVisible();
		await expect( editor.page.getByText( 'Orientation' ) ).toBeVisible();
	} );

	test( 'should add correct layout CSS class when modifying layout settings', async ( {
		editor,
		pageObject,
	} ) => {
		await pageObject.addProductFiltersBlock( { cleanContent: true } );

		const activeBlock = editor.canvas.getByLabel(
			blockData.selectors.editor.label
		);

		await expect( activeBlock ).toBeVisible();

		await activeBlock.click();

		const chipsBlock = editor.canvas.getByLabel(
			blockData.selectors.editor.innerBlocks.chips.label
		);

		await expect( chipsBlock ).toBeVisible();
		await editor.selectBlocks( chipsBlock );

		await editor.openDocumentSettingsSidebar();

		await editor.page.getByLabel( 'Space between items' ).click();
		await expect( chipsBlock ).toHaveClass(
			/is-content-justification-space-between/
		);

		await editor.page.getByLabel( 'Justify items right' ).click();
		await expect( chipsBlock ).toHaveClass(
			/is-content-justification-right/
		);

		await editor.page.getByLabel( 'Justify items center' ).click();
		await expect( chipsBlock ).toHaveClass(
			/is-content-justification-center/
		);

		await editor.page.getByLabel( 'Justify items left' ).click();
		await expect( chipsBlock ).toHaveClass(
			/is-content-justification-left/
		);

		await editor.page.getByRole( 'button', { name: 'Horizontal' } ).click();
		await expect( chipsBlock ).toHaveClass( /is-horizontal/ );

		await editor.page.getByRole( 'button', { name: 'Vertical' } ).click();
		await expect( chipsBlock ).toHaveClass( /is-vertical/ );
	} );
} );
