/**
 * External dependencies
 */
import {
	test,
	expect,
	BLOCK_THEME_WITH_TEMPLATES_SLUG,
} from '@woocommerce/e2e-utils';
import { saveAndActivateTemplate } from './constants';

const testData = {
	permalink: '/product/belt',
	templateName: 'Single Product Belt',
	templatePath: 'single-product-belt',
	templateType: 'wp_registered_template' as
		| 'wp_registered_template'
		| 'wp_template_part',
};

const userText = 'Hello World in the Belt template';
const themeTemplateText = 'Single Product Belt template loaded from theme';

test.describe( 'Single Product Template', () => {
	test.beforeEach( async ( { requestUtils } ) => {
		await requestUtils.activateTheme( BLOCK_THEME_WITH_TEMPLATES_SLUG );
	} );

	test( 'loads the theme template for a specific product using the product slug and it can be customized', async ( {
		admin,
		editor,
		page,
	} ) => {
		// Edit the theme template.
		await admin.visitSiteEditor( {
			postId: `${ BLOCK_THEME_WITH_TEMPLATES_SLUG }//${ testData.templatePath }`,
			postType: testData.templateType,
			canvas: 'edit',
		} );

		await editor.insertBlock( {
			name: 'core/paragraph',
			attributes: { content: userText },
		} );
		await saveAndActivateTemplate( {
			editor,
			page,
		} );
		await page.goto( testData.permalink );

		// Verify edits are visible in the frontend.
		await expect(
			page.getByText( themeTemplateText ).first()
		).toBeVisible();
		await expect( page.getByText( userText ).first() ).toBeVisible();

		// Revert edition and verify the template from the theme is used.
		await admin.visitSiteEditor( {
			postType: testData.templateType,
		} );
		// TODO: Raise a problem that custom templates are displayed with slugs not titles
		await editor.revertTemplate( {
			templateName: testData.templatePath,
		} );
		await page.goto( testData.permalink );

		await expect(
			page.getByText( themeTemplateText ).first()
		).toBeVisible();
		await expect( page.getByText( userText ) ).toHaveCount( 0 );
	} );
} );
