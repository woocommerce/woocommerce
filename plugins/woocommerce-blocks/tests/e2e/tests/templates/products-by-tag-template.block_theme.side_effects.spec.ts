/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';
import {
	BLOCK_THEME_SLUG,
	BLOCK_THEME_WITH_TEMPLATES_SLUG,
	cli,
} from '@woocommerce/e2e-utils';

const permalink = '/product-tag/recommended/';
const templateName = 'Products by Tag';
const templatePath = `${ BLOCK_THEME_WITH_TEMPLATES_SLUG }//taxonomy-product_tag`;
const templateType = 'wp_template';

test.describe( 'Product by Tag template', async () => {
	test( "theme template has priority over WooCommerce's and can be modified", async ( {
		admin,
		editorUtils,
		page,
	} ) => {
		// Switch to block theme with WooCommerce templates.
		await cli(
			`npm run wp-env run tests-cli -- wp theme activate ${ BLOCK_THEME_WITH_TEMPLATES_SLUG }`
		);

		// Edit the theme template.
		await admin.visitSiteEditor( {
			postId: templatePath,
			postType: templateType,
		} );
		await editorUtils.enterEditMode();
		await editorUtils.closeWelcomeGuideModal();
		await editorUtils.editor.insertBlock( {
			name: 'core/paragraph',
			attributes: { content: 'Hello World in the theme template' },
		} );
		await editorUtils.saveTemplate();

		// Verify the template is the one modified by the user.
		await page.goto( permalink );
		await expect(
			page.getByText( 'Hello World in the theme template' ).first()
		).toBeVisible();

		// Revert edition and verify the template from the theme is used.
		await admin.visitAdminPage(
			'site-editor.php',
			`path=/${ templateType }/all`
		);
		await editorUtils.revertTemplateCustomizations( templateName );
		await page.goto( permalink );

		await expect(
			page
				.getByText( 'Products by Tag template loaded from theme' )
				.first()
		).toBeVisible();
		await expect(
			page.getByText( 'Hello World in the template' )
		).toHaveCount( 0 );

		// Switch back to default block theme.
		await cli(
			`npm run wp-env run tests-cli -- wp theme activate ${ BLOCK_THEME_SLUG }`
		);
	} );
} );
