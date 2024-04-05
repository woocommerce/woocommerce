/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';
import { cli } from '@woocommerce/e2e-utils';

test.describe( 'Legacy templates', async () => {
	test.beforeAll( async ( { requestUtils } ) => {
		await requestUtils.deleteAllTemplates( 'wp_template' );
	} );

	test.afterEach( async ( { requestUtils } ) => {
		await requestUtils.deleteAllTemplates( 'wp_template' );
	} );

	test( 'woocommerce//* name is supported', async ( {
		admin,
		page,
		editor,
	} ) => {
		const templateName = 'single-product';
		const customText = 'This is a customized template.';

		await test.step( 'Customize existing template to create DB entry', async () => {
			await admin.visitSiteEditor( {
				postId: `woocommerce/woocommerce//${ templateName }`,
				postType: 'wp_template',
				canvas: 'edit',
			} );

			const title = editor.canvas.getByText( 'Title' );

			await title.click();
			await title.press( 'Enter' );

			const emptyBlock = editor.canvas
				.getByLabel( 'Empty block' )
				.first();

			await emptyBlock.fill( customText );
			await page.keyboard.press( 'Escape' );

			await expect( editor.canvas.getByText( customText ) ).toBeVisible();

			await editor.saveSiteEditorEntities();
		} );

		await test.step( 'Update created term to legacy format in the DB', async () => {
			const cliOutput = await cli(
				`npm run wp-env run tests-cli -- \
					wp term update wp_theme woocommerce-woocommerce \
						--by="slug" \
						--name="woocommerce" \
						--slug="woocommerce"`
			);

			expect( cliOutput.stdout ).toContain( 'Success: Term updated.' );
		} );

		await test.step( 'Verify the template can be loaded via legacy ID', async () => {
			await admin.visitSiteEditor( {
				postId: `woocommerce//${ templateName }`,
				postType: 'wp_template',
				canvas: 'edit',
			} );

			await expect( editor.canvas.getByText( customText ) ).toBeVisible();
		} );

		await test.step( 'Revert term update', async () => {
			const cliOutput = await cli(
				`npm run wp-env run tests-cli -- \
					wp term update wp_theme woocommerce \
						--by="slug" \
						--name="woocommerce/woocommerce" \
						--slug="woocommerce-woocommerce"`
			);

			expect( cliOutput.stdout ).toContain( 'Success: Term updated.' );
		} );
	} );
} );
