/**
 * External dependencies
 */
import { devices } from '@playwright/test';
import { test, expect, BLOCK_THEME_SLUG } from '@woocommerce/e2e-utils';

const blockData = {
	name: 'woocommerce/product-image-gallery',
	productPage: '/product/hoodie/',
};

test.describe( `${ blockData.name } frontend`, () => {
	/**
	 * Note: These tests are run on a mobile device because the tap() method is required,
	 * which is not supported on desktop devices.
	 *
	 * @see https://playwright.dev/docs/api/class-locator#locator-tap
	 */
	test.use( {
		viewport: { width: 412, height: 915 },
		userAgent: devices[ 'Pixel 7' ].userAgent,
		hasTouch: true,
	} );

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

test.describe( `${ blockData.name } editor`, () => {
	test( 'can be migrated to the Product Gallery block', async ( {
		page,
		editor,
		admin,
	} ) => {
		await admin.visitSiteEditor( {
			postId: `${ BLOCK_THEME_SLUG }//single-product`,
			postType: 'wp_template',
			canvas: 'edit',
		} );

		const productImageGalleryBlock = await editor.getBlockByName(
			blockData.name
		);
		await editor.selectBlocks( productImageGalleryBlock );

		await expect(
			editor.canvas.getByLabel( 'Block: Product Gallery' )
		).toBeHidden();

		await page
			.getByRole( 'button', {
				name: 'Upgrade to the new Product Gallery block',
			} )
			.click();

		await expect(
			editor.canvas.getByLabel( 'Block: Product Gallery' )
		).toBeVisible();
	} );
} );
