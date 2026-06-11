/**
 * External dependencies
 */
import { test as base, expect, BLOCK_THEME_SLUG } from '@woocommerce/e2e-utils';

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
			layoutWrapper:
				'.wp-block-woocommerce-product-filters-is-layout-flex',
			blocks: {
				filters: {
					title: 'Product Filters',
					label: 'Block: Product Filters',
				},
				overlay: {
					title: 'Overlay Navigation',
					label: 'Block: Overlay Navigation',
				},
			},
		},
	},
	slug: 'archive-product',
	productPage: '/product/hoodie/',
	shopPage: '/shop/',
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

	test( 'should be visible and contain correct inner blocks', async ( {
		editor,
		pageObject,
	} ) => {
		await pageObject.addProductFiltersBlock( { cleanContent: true } );

		const block = editor.canvas.getByLabel(
			blockData.selectors.editor.blocks.filters.label
		);
		await expect( block ).toBeVisible();

		const activeFilterBlock = block.getByLabel( 'Block: Active' );
		await expect( activeFilterBlock ).toBeVisible();

		const colorHeading = block.getByText( 'Color', {
			exact: true,
		} );
		const colorFilterBlock = block.getByLabel( 'Block: Color' );
		const expectedColorFilterOptions = [
			'Blue',
			'Green',
			'Gray',
			'Red',
			'Yellow',
		];
		await expect( colorHeading ).toBeVisible();
		await expect( colorFilterBlock ).toBeVisible();
		for ( const option of expectedColorFilterOptions ) {
			await expect( colorFilterBlock ).toContainText( option );
		}
	} );

	test( 'should contain the correct inner block names in the list view', async ( {
		editor,
		pageObject,
	} ) => {
		await pageObject.addProductFiltersBlock( { cleanContent: true } );

		const block = editor.canvas.getByLabel(
			blockData.selectors.editor.blocks.filters.label
		);
		await expect( block ).toBeVisible();

		await pageObject.page.getByLabel( 'Document Overview' ).click();
		const listView = pageObject.page.getByLabel( 'List View' );

		await expect( listView ).toBeVisible();

		const productFiltersBlockListItem = listView.getByRole( 'link', {
			name: blockData.selectors.editor.blocks.filters.title,
		} );
		await expect( productFiltersBlockListItem ).toBeVisible();
		const listViewExpander =
			pageObject.page.getByTestId( 'list-view-expander' );
		const listViewExpanderIcon = listViewExpander.locator( 'svg' );

		await listViewExpanderIcon.click();

		const productFilterHeadingListItem = listView.getByText( 'Filters', {
			exact: true,
		} );
		await expect( productFilterHeadingListItem ).toBeVisible();

		const productFilterActiveBlocksListItem =
			listView.getByText( 'Active' );
		await expect( productFilterActiveBlocksListItem ).toBeVisible();

		const productFilterAttributeBlockListItem = listView.getByText(
			'Color' // it must select the attribute with the highest product count
		);
		await expect( productFilterAttributeBlockListItem ).toBeVisible();
	} );
} );
