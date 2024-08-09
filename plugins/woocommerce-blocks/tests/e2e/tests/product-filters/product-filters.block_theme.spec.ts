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
			layoutWrapper:
				'.wp-block-woocommerce-product-filters-is-layout-flex',
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

		const activeHeading = block.getByText( 'Active', { exact: true } );
		const activeFilterBlock = block
			.getByLabel( 'Block: Filter Options' )
			.and(
				editor.canvas.locator(
					'[data-type="woocommerce/product-filter-active"]'
				)
			);
		await expect( activeHeading ).toBeVisible();
		await expect( activeFilterBlock ).toBeVisible();

		const priceHeading = block.getByText( 'Price', {
			exact: true,
		} );
		const priceFilterBlock = block
			.getByLabel( 'Block: Filter Options' )
			.and(
				editor.canvas.locator(
					'[data-type="woocommerce/product-filter-price"]'
				)
			);
		await expect( priceHeading ).toBeVisible();
		await expect( priceFilterBlock ).toBeVisible();

		const statusHeading = block.getByText( 'Status', {
			exact: true,
		} );
		const statusFilterBlock = block
			.getByLabel( 'Block: Filter Options' )
			.and(
				editor.canvas.locator(
					'[data-type="woocommerce/product-filter-stock-status"]'
				)
			);
		await expect( statusHeading ).toBeVisible();
		await expect( statusFilterBlock ).toBeVisible();

		const colorHeading = block.getByText( 'Color', {
			exact: true,
		} );
		const colorFilterBlock = block
			.getByLabel( 'Block: Filter Options' )
			.and(
				editor.canvas.locator(
					'[data-type="woocommerce/product-filter-attribute"]'
				)
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

		const ratingHeading = block.getByText( 'Rating', {
			exact: true,
		} );
		const ratingFilterBlock = block
			.getByLabel( 'Block: Filter Options' )
			.and(
				editor.canvas.locator(
					'[data-type="woocommerce/product-filter-rating"]'
				)
			);
		await expect( ratingHeading ).toBeVisible();
		await expect( ratingFilterBlock ).toBeVisible();
	} );

	test( 'should contain the correct inner block names in the list view', async ( {
		editor,
		pageObject,
	} ) => {
		await pageObject.addProductFiltersBlock( { cleanContent: true } );

		const block = editor.canvas.getByLabel(
			'Block: Product Filters (Experimental)'
		);
		await expect( block ).toBeVisible();

		await pageObject.page.getByLabel( 'Document Overview' ).click();
		const listView = pageObject.page.getByLabel( 'List View' );

		await expect( listView ).toBeVisible();

		const productFiltersBlockListItem = listView.getByRole( 'link', {
			name: 'Product Filters (Experimental)',
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

		const productFilterActiveBlocksListItem = listView.getByText(
			'Active (Experimental)'
		);
		await expect( productFilterActiveBlocksListItem ).toBeVisible();

		const productFilterPriceBlockListItem = listView.getByText(
			'Price (Experimental)'
		);
		await expect( productFilterPriceBlockListItem ).toBeVisible();

		const productFilterStatusBlockListItem = listView.getByText(
			'Status (Experimental)'
		);
		await expect( productFilterStatusBlockListItem ).toBeVisible();

		const productFilterAttributeBlockListItem = listView.getByText(
			'Color (Experimental)' // it must select the attribute with the highest product count
		);
		await expect( productFilterAttributeBlockListItem ).toBeVisible();

		const productFilterRatingBlockListItem = listView.getByText(
			'Rating (Experimental)'
		);
		await expect( productFilterRatingBlockListItem ).toBeVisible();
	} );

	test( 'should display the correct inspector style controls', async ( {
		editor,
		pageObject,
	} ) => {
		await pageObject.addProductFiltersBlock( { cleanContent: true } );

		const block = editor.canvas.getByLabel(
			'Block: Product Filters (Experimental)'
		);
		await expect( block ).toBeVisible();

		await editor.openDocumentSettingsSidebar();

		await editor.page.getByRole( 'tab', { name: 'Styles' } ).click();

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

		// Block spacing settings
		await expect(
			editor.page.getByText( 'DimensionsBlock spacing' )
		).toBeVisible();
	} );

	test( 'should display the correct inspector setting controls', async ( {
		editor,
		pageObject,
	} ) => {
		await pageObject.addProductFiltersBlock( { cleanContent: true } );

		const block = editor.canvas.getByLabel(
			'Block: Product Filters (Experimental)'
		);
		await expect( block ).toBeVisible();

		await editor.openDocumentSettingsSidebar();

		// Layout settings
		await expect(
			editor.page.getByText( 'LayoutJustificationOrientation' )
		).toBeVisible();

		// Overlay settings
		const overlayModeSettings = [ 'Never', 'Mobile', 'Always' ];
		const overlayButtonSettings = [
			'Label and icon',
			'Label only',
			'Icon only',
		];
		const overlayIconsSettings = [
			'Filter icon 1',
			'Filter icon 2',
			'Filter icon 3',
			'Filter icon 4',
		];

		await expect( editor.page.getByText( 'Overlay' ) ).toBeVisible();

		for ( const mode of overlayModeSettings ) {
			await expect( editor.page.getByText( mode ) ).toBeVisible();
		}

		await editor.page.getByLabel( 'Mobile' ).click();
		await expect( editor.page.getByText( 'BUTTON' ) ).toBeVisible();

		for ( const mode of overlayButtonSettings ) {
			await expect( editor.page.getByText( mode ) ).toBeVisible();
		}

		for ( const mode of overlayIconsSettings ) {
			await expect( editor.page.getByLabel( mode ) ).toBeVisible();
		}

		await expect( editor.page.getByText( 'ICON SIZE' ) ).toBeVisible();
		await expect( editor.page.getByText( 'Edit overlay' ) ).toBeVisible();

		await editor.page.getByLabel( 'Always' ).click();

		await expect( editor.page.getByText( 'BUTTON' ) ).toBeHidden();

		for ( const mode of overlayButtonSettings ) {
			await expect( editor.page.getByText( mode ) ).toBeHidden();
		}

		for ( const mode of overlayIconsSettings ) {
			await expect( editor.page.getByLabel( mode ) ).toBeHidden();
		}

		await expect( editor.page.getByText( 'Edit overlay' ) ).toBeVisible();

		await editor.page.getByLabel( 'Mobile' ).click();

		await editor.page.locator( 'input[value="label"]' ).click();

		for ( const mode of overlayIconsSettings ) {
			await expect( editor.page.getByLabel( mode ) ).toBeHidden();
		}

		await expect( editor.page.getByText( 'Edit overlay' ) ).toBeVisible();
	} );

	test( 'Layout > default to vertical stretch', async ( {
		editor,
		pageObject,
	} ) => {
		await pageObject.addProductFiltersBlock( { cleanContent: true } );

		const block = editor.canvas.getByLabel(
			'Block: Product Filters (Experimental)'
		);
		await expect( block ).toBeVisible();

		await editor.openDocumentSettingsSidebar();

		const layoutSettings = editor.page.getByText(
			'LayoutJustificationOrientation'
		);
		await expect(
			layoutSettings.getByLabel( 'Justify items left' )
		).not.toHaveAttribute( 'data-active-item' );
		await expect(
			layoutSettings.getByLabel( 'Stretch items' )
		).toHaveAttribute( 'data-active-item' );
		await expect(
			layoutSettings.getByLabel( 'Horizontal' )
		).not.toHaveAttribute( 'data-active-item' );
		await expect( layoutSettings.getByLabel( 'Vertical' ) ).toHaveAttribute(
			'data-active-item'
		);
	} );

	test( 'Layout > Justification: changing option should update the preview', async ( {
		editor,
		pageObject,
	} ) => {
		await pageObject.addProductFiltersBlock( { cleanContent: true } );

		const block = editor.canvas.getByLabel(
			'Block: Product Filters (Experimental)'
		);
		await expect( block ).toBeVisible();

		await editor.openDocumentSettingsSidebar();

		const layoutSettings = editor.page.getByText(
			'LayoutJustificationOrientation'
		);
		await layoutSettings.getByLabel( 'Justify items left' ).click();
		await expect(
			layoutSettings.getByLabel( 'Justify items left' )
		).toHaveAttribute( 'data-active-item' );
		await expect(
			block.locator( blockData.selectors.editor.layoutWrapper )
		).toHaveCSS( 'align-items', 'flex-start' );

		await layoutSettings.getByLabel( 'Justify items center' ).click();
		await expect(
			layoutSettings.getByLabel( 'Justify items center' )
		).toHaveAttribute( 'data-active-item' );
		await expect(
			block.locator( blockData.selectors.editor.layoutWrapper )
		).toHaveCSS( 'align-items', 'center' );
	} );

	test( 'Layout > Orientation: changing option should update the preview', async ( {
		editor,
		pageObject,
	} ) => {
		await pageObject.addProductFiltersBlock( { cleanContent: true } );

		const block = editor.canvas.getByLabel(
			'Block: Product Filters (Experimental)'
		);
		await expect( block ).toBeVisible();

		await editor.openDocumentSettingsSidebar();

		const layoutSettings = editor.page.getByText(
			'LayoutJustificationOrientation'
		);
		await layoutSettings.getByLabel( 'Horizontal' ).click();
		await expect(
			layoutSettings.getByLabel( 'Stretch items' )
		).toBeHidden();
		await expect(
			layoutSettings.getByLabel( 'Space between items' )
		).toBeVisible();
		await expect(
			block.locator( ':text("Status"):right-of(:text("Price"))' )
		).toBeVisible();

		await layoutSettings.getByLabel( 'Vertical' ).click();
		await expect(
			block.locator( ':text("Status"):below(:text("Price"))' )
		).toBeVisible();
	} );

	test( 'Dimentions > Block spacing: changing option should update the preview', async ( {
		editor,
		pageObject,
	} ) => {
		await pageObject.addProductFiltersBlock( { cleanContent: true } );

		const block = editor.canvas.getByLabel(
			'Block: Product Filters (Experimental)'
		);
		await expect( block ).toBeVisible();

		await editor.openDocumentSettingsSidebar();

		await editor.page.getByRole( 'tab', { name: 'Styles' } ).click();

		const blockSpacingSettings = editor.page.getByLabel( 'Block spacing' );

		await blockSpacingSettings.fill( '4' );
		await expect(
			block.locator( blockData.selectors.editor.layoutWrapper )
		).not.toHaveCSS( 'gap', '0px' );

		await blockSpacingSettings.fill( '0' );
		await expect(
			block.locator( blockData.selectors.editor.layoutWrapper )
		).toHaveCSS( 'gap', '0px' );
	} );
} );
