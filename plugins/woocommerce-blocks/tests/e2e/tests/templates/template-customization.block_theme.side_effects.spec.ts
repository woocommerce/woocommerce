/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';
import {
	BLOCK_THEME_SLUG,
	BLOCK_THEME_WITH_TEMPLATES_SLUG,
} from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { CUSTOMIZABLE_WC_TEMPLATES } from './constants';

const testToRun = CUSTOMIZABLE_WC_TEMPLATES.filter(
	( data ) => data.canBeOverriddenByThemes
);

for ( const testData of testToRun ) {
	const userText = `Hello World in the ${ testData.templateName } template`;

	test.describe( `${ testData.templateName } template`, () => {
		test( `user-modified ${ testData.templateName } template is attached to the theme`, async ( {
			page,
			editor,
			requestUtils,
			editorUtils,
			frontendUtils,
		} ) => {
			await editorUtils.visitTemplateEditor(
				testData.templateName,
				testData.templateType
			);
			await editor.insertBlock( {
				name: 'core/paragraph',
				attributes: { content: userText },
			} );
			await editor.saveSiteEditorEntities();

			await testData.visitPage( { frontendUtils, page } );
			await expect( page.getByText( userText ).first() ).toBeVisible();

			await requestUtils.activateTheme( BLOCK_THEME_WITH_TEMPLATES_SLUG );

			await testData.visitPage( { frontendUtils, page } );
			await expect( page.getByText( userText ) ).toHaveCount( 0 );

			await requestUtils.activateTheme( BLOCK_THEME_SLUG );
		} );
	} );
}
