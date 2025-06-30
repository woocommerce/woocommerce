/**
 * External dependencies
 */
import { TemplateCompiler, test as base, expect } from '@woocommerce/e2e-utils';

const test = base.extend< { templateCompiler: TemplateCompiler } >( {
	templateCompiler: async ( { requestUtils }, use ) => {
		const compiler = await requestUtils.createTemplateFromFile(
			'archive-product_attribute-filter'
		);
		await use( compiler );
	},
} );

test.describe( 'woocommerce/product-filters - Frontend', () => {
	test.describe( 'Overlay', () => {
		test.beforeEach( async ( { templateCompiler, page } ) => {
			await templateCompiler.compile( {
				attributes: {
					attributeId: 1,
				},
			} );

			await page.addInitScript( () => {
				// Mock the wc global variable.
				if ( typeof window.wc === 'undefined' ) {
					window.wc = {
						wcSettings: {
							getSetting() {
								return true;
							},
						},
					};
				}
			} );
		} );

		test( 'On mobile, overlay can be open and close.', async ( {
			page,
		} ) => {
			await page.setViewportSize( { width: 400, height: 600 } );
			await page.goto( '/shop' );

			const openOverlayButton = page.getByRole( 'button', {
				name: 'Filter products',
			} );
			const overlay = page.getByRole( 'dialog' );

			await expect( openOverlayButton ).toBeVisible();
			await expect( overlay ).not.toBeInViewport();

			await openOverlayButton.click();

			await expect( overlay ).toBeInViewport();

			// Close overlay by clicking close button.
			const closeOverlayButton = page.getByRole( 'button', {
				name: 'Close',
			} );

			await expect( closeOverlayButton ).toBeVisible();

			await closeOverlayButton.click();

			await expect( overlay ).not.toBeInViewport();

			// Close overlay by hitting Esc.
			await openOverlayButton.click();

			await expect( overlay ).toBeInViewport();

			await page.keyboard.press( 'Escape' );

			await expect( overlay ).not.toBeInViewport();

			// Close overlay by clicking Apply button.
			await openOverlayButton.click();

			await expect( overlay ).toBeInViewport();

			const applyButton = page.getByRole( 'button', {
				name: 'Apply',
			} );

			await expect( applyButton ).toBeVisible();

			await applyButton.click();

			await expect( overlay ).not.toBeInViewport();
		} );

		// Skipping these tests until we can move this block to @wordpress/interactivity.
		// eslint-disable-next-line playwright/no-skipped-test
		test.skip( 'filter is working inside overlay', async ( { page } ) => {
			await page.setViewportSize( { width: 400, height: 600 } );
			await page.goto( '/shop' );

			await page
				.getByRole( 'button', {
					name: 'Filter products',
				} )
				.click();

			await page.getByText( 'Gray' ).click();

			await page
				.getByRole( 'button', {
					name: 'Apply',
				} )
				.click();

			// wait for navigation
			await page.waitForURL( /.*filter_color=gray.*/ );

			const products = page.locator( '.wc-block-product' );

			await expect( products ).toHaveCount( 2 );
		} );
	} );
} );
