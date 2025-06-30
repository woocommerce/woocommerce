/**
 * External dependencies
 */
import { devices } from '@playwright/test';
import { test, expect } from '@woocommerce/e2e-utils';

const blockData = {
	name: 'woocommerce/product-image-gallery',
	productPage: '/product/hoodie/',
};

/**
 * Note: These tests are run on a mobile device because the tap() method is required,
 * which is not supported on desktop devices.
 *
 * @see https://playwright.dev/docs/api/class-locator#locator-tap
 */
test.use( { ...devices[ 'Pixel 7' ] } );

test.describe( `${ blockData.name }`, () => {
	test( 'should not switch to the next image when the user cursor is focused on the rating with keyboard', async ( {
		page,
	} ) => {
		await page.goto( blockData.productPage );

		const activeImageSrc = await page
			.locator( '.flex-active' )
			.getAttribute( 'src' );

		await page.getByRole( 'tab', { name: 'Reviews' } ).click();

		const rating = page.locator( '.star-3' );
		await rating.click();
		await rating.focus();

		await page.keyboard.press( 'ArrowRight' );

		const newActiveImage = page.locator( '.flex-active' );

		await expect( newActiveImage ).toHaveAttribute(
			'src',
			activeImageSrc as string
		);
	} );

	test( 'should not switch to the next image when the user cursor is focused on the tabs with keyboard', async ( {
		page,
	} ) => {
		await page.goto( blockData.productPage );

		const activeImageSrc = await page
			.locator( '.flex-active' )
			.getAttribute( 'src' );

		await page.getByRole( 'tab', { name: 'Reviews' } ).focus();

		await page.keyboard.press( 'ArrowRight' );

		const newActiveImage = page.locator( '.flex-active' );

		await expect( newActiveImage ).toHaveAttribute(
			'src',
			activeImageSrc as string
		);
	} );

	test( 'should switch to the next image when the user cursor is focused on tabs with touch event', async ( {
		page,
	} ) => {
		await page.goto( blockData.productPage );

		const activeImageSrc = await page
			.locator( '.flex-active' )
			.getAttribute( 'src' );

		await page.getByRole( 'tab', { name: 'Description' } ).click();
		await page.getByRole( 'tab', { name: 'Reviews' } ).click();

		await page
			.locator( '.flex-control-nav.flex-control-thumbs' )
			.locator( 'img' )
			.nth( 2 )
			.tap();

		const newActiveImage = page.locator( '.flex-active' );

		await expect( newActiveImage ).not.toHaveAttribute(
			'src',
			activeImageSrc as string
		);
	} );
} );
