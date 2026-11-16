/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { REGULAR_PRICED_PRODUCT_NAME } from '../checkout/constants';

test.describe( 'Mini-Cart Block (admin)', () => {
	// eslint-disable-next-line playwright/no-skipped-test
	test.skip( 'should update badge colors when header background changes', async ( {
		page,
		admin,
		editor,
		frontendUtils,
	} ) => {
		// First, change the header background color in the site editor.
		await admin.visitSiteEditor( {
			postId: 'twentytwentyfour//header',
			postType: 'wp_template_part',
			canvas: 'edit',
		} );

		// Select the mini-cart block to get access to its parent (the header row).
		const miniCartBlock = editor.canvas.locator(
			'[data-type="woocommerce/mini-cart"]'
		);
		await miniCartBlock.click();

		// Select the parent Row block that contains the mini-cart.
		// Use the block toolbar to select parent.
		await editor.clickBlockToolbarButton( 'Select parent block: Row' );

		// Now open the Styles panel and set background color.
		await editor.openDocumentSettingsSidebar();

		// Click on the Styles tab.
		const stylesTab = page.getByRole( 'tab', { name: 'Styles' } );
		if ( await stylesTab.isVisible() ) {
			await stylesTab.click();
		}

		// Find and click the background color control.
		const bgColorButton = page
			.getByRole( 'button', { name: 'Background' } )
			.first();
		await bgColorButton.click();

		// Select "Contrast" preset color (black).
		await page
			.getByRole( 'option', { name: 'Contrast', exact: true } )
			.click();

		// Extract the background color hex value from the editor UI.
		const parentBgColorHex = await page
			.locator( '.components-color-palette__custom-color-value' )
			.textContent();

		// Close the background color popover by clicking outside.
		await stylesTab.click();

		// Find and click the text color control.
		const textColorButton = page
			.getByRole( 'button', { name: 'Text' } )
			.first();
		await textColorButton.click();

		// Select "Base" preset color (white).
		await page.getByRole( 'option', { name: 'Base', exact: true } ).click();

		// Extract the text color hex value from the editor UI.
		const parentTextColorHex = await page
			.locator( '.components-color-palette__custom-color-value' )
			.textContent();

		// Save the changes.
		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );

		// Add an item to cart (use a product that's on the first page).
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );

		// Verify color values were extracted from the editor.
		expect( parentBgColorHex ).toBeTruthy();
		expect( parentTextColorHex ).toBeTruthy();

		// Helper to convert hex color to rgb format.
		const hexToRgb = ( hex: string ) => {
			const cleanHex = hex.replace( '#', '' );
			const r = parseInt( cleanHex.slice( 0, 2 ), 16 );
			const g = parseInt( cleanHex.slice( 2, 4 ), 16 );
			const b = parseInt( cleanHex.slice( 4, 6 ), 16 );
			return `rgb(${ r }, ${ g }, ${ b })`;
		};

		// Verify the badge has the correct colors:
		// - Badge background = parent's text color (inverted)
		// - Badge text = parent's background color (inverted)
		const badge = page.locator( '.wc-block-mini-cart__badge' );
		await expect( badge ).toHaveCSS(
			'background-color',
			hexToRgb( parentTextColorHex as string )
		);
		await expect( badge ).toHaveCSS(
			'color',
			hexToRgb( parentBgColorHex as string )
		);

		// Navigate to the next page using client-side navigation.
		await page.getByRole( 'link', { name: 'Next Page' } ).click();

		// Await for the navigation to happen.
		await expect( page ).toHaveURL( /page\/2\/?$/ );

		// Verify the badge colors persist after navigation.
		await expect( badge ).toHaveCSS(
			'background-color',
			hexToRgb( parentTextColorHex as string )
		);
		await expect( badge ).toHaveCSS(
			'color',
			hexToRgb( parentBgColorHex as string )
		);
	} );
} );
