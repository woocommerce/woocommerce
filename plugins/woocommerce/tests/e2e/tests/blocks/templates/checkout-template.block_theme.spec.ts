/**
 * External dependencies
 */
import { test, expect, BLOCK_THEME_SLUG } from '@woocommerce/e2e-utils';

const templatePath = `${ BLOCK_THEME_SLUG }//page-checkout`;
const templateType = 'wp_template';

test.describe( 'Test the checkout template', () => {
	test( 'Template can be opened in the site editor', async ( {
		admin,
		editor,
	} ) => {
		await admin.visitSiteEditor( {
			postId: templatePath,
			postType: templateType,
			canvas: 'edit',
			showWelcomeGuide: false,
		} );
		const block = editor.canvas.getByLabel( 'Block: Checkout', {
			exact: true,
		} );
		await expect( block ).toBeVisible();
	} );

	test( 'Template can be accessed from the page editor', async ( {
		admin,
		editor,
		page,
		wpCoreVersion,
	} ) => {
		await admin.visitSiteEditor( {
			postType: 'page',
			showWelcomeGuide: false,
		} );
		await editor.page
			.getByRole( 'button', { name: 'Checkout', exact: true } )
			.click();
		await editor.canvas.locator( 'body' ).click();

		await expect(
			editor.canvas.getByRole( 'button', {
				name: 'Place Order',
			} )
		).toBeVisible();

		await editor.openDocumentSettingsSidebar();
		// On 7.2 and above, the selectors for editing the template have changed: https://make.wordpress.org/core/2026/09/17/call-for-testing-dataform-editor-inspector/
		// eslint-disable-next-line playwright/no-conditional-in-test
		if ( wpCoreVersion >= 7.2 ) {
			await page
				.getByRole( 'button', {
					name: 'Template: Page: Checkout',
					exact: true,
				} )
				.click();
			await page
				.getByRole( 'button', { name: 'Edit', exact: true } )
				.click();
		} else {
			await page.getByLabel( 'Template options' ).click();
			await page
				.getByRole( 'menuitem', { name: 'Edit template' } )
				.click();
		}

		await expect
			.poll( () =>
				page.evaluate( () =>
					window.wp.data.select( 'core/editor' ).getCurrentPostId()
				)
			)
			.toBe( templatePath );

		await expect(
			editor.canvas.getByRole( 'button', {
				name: 'Place Order',
			} )
		).toBeVisible();
	} );
} );
