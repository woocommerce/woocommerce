/**
 * External dependencies
 */
import {
	test,
	expect,
	BLOCK_THEME_SLUG,
	BLOCK_THEME_WITH_TEMPLATES_SLUG,
	type RequestUtils,
} from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { CUSTOMIZABLE_WC_TEMPLATES } from './constants';

type TemplatePartResponse = {
	id: string;
	wp_id: number;
	content: { raw: string };
};

const markerCount = ( content: string, marker: string ) =>
	content.split( marker ).length - 1;

const getTemplatePart = async (
	requestUtils: RequestUtils,
	id: string
): Promise< TemplatePartResponse > =>
	requestUtils.rest< TemplatePartResponse >( {
		method: 'GET',
		path: `/wp/v2/template-parts/${ id }?context=edit`,
	} );

const assertPluginTemplatePartBase = (
	templatePart: TemplatePartResponse,
	expectedId: string,
	marker: string
) => {
	if (
		templatePart.id !== expectedId ||
		templatePart.content.raw.length === 0 ||
		markerCount( templatePart.content.raw, marker ) !== 0
	) {
		throw new Error(
			`Template part did not start from the expected plugin base: ${ templatePart.id }`
		);
	}
};

const assertCustomTemplatePart = (
	templatePart: TemplatePartResponse,
	expectedId: string,
	expectedContent: string,
	marker: string
) => {
	if (
		templatePart.id !== expectedId ||
		! Number.isInteger( templatePart.wp_id ) ||
		templatePart.wp_id <= 0 ||
		templatePart.content.raw !== expectedContent ||
		markerCount( templatePart.content.raw, marker ) !== 1
	) {
		throw new Error(
			`Template part customization did not match the requested state: ${ templatePart.id }`
		);
	}
};

const customizeTemplatePartViaRest = async (
	requestUtils: RequestUtils,
	id: string,
	marker: string
) => {
	const base = await getTemplatePart( requestUtils, id );
	assertPluginTemplatePartBase( base, id, marker );

	const expectedContent = `${ base.content.raw }
<!-- wp:paragraph -->
<p>${ marker }</p>
<!-- /wp:paragraph -->`;
	const response = await requestUtils.rest< TemplatePartResponse >( {
		method: 'PUT',
		path: `/wp/v2/template-parts/${ id }?context=edit`,
		data: {
			content: expectedContent,
		},
	} );
	const saved = await getTemplatePart( requestUtils, id );

	assertCustomTemplatePart( response, id, expectedContent, marker );
	assertCustomTemplatePart( saved, id, expectedContent, marker );

	if ( saved.id !== response.id || saved.wp_id !== response.wp_id ) {
		throw new Error(
			`Template part customization identity changed after saving: ${ id }`
		);
	}
};

test.describe( 'Template customization', () => {
	CUSTOMIZABLE_WC_TEMPLATES.forEach( ( testData ) => {
		const userText = `Hello World in the ${ testData.templateName } template`;
		const fallbackTemplateUserText = `Hello World in the fallback ${ testData.templateName } template`;
		const templateTypeName =
			testData.templateType === 'wp_template'
				? 'template'
				: 'template part';

		test( `"${ testData.templateName }" template can be modified and reverted`, async ( {
			admin,
			frontendUtils,
			editor,
			page,
			requestUtils,
		} ) => {
			if (
				'isTaxonomyTemplate' in testData &&
				testData.isTaxonomyTemplate
			) {
				await admin.visitSiteEditor( {
					postType: 'wp_template',
				} );

				await editor.createTemplate( {
					templateName: testData.templateName,
				} );
			} else {
				const templateSlug =
					testData.templateType === 'wp_template'
						? BLOCK_THEME_SLUG
						: 'woocommerce/woocommerce';
				await admin.visitSiteEditor( {
					postId: `${ templateSlug }//${ testData.templatePath }`,
					postType: testData.templateType,
					canvas: 'edit',
				} );
			}

			await editor.canvas.locator( 'body' ).waitFor( { timeout: 20000 } );

			await editor.insertBlock( {
				name: 'core/paragraph',
				attributes: { content: userText },
			} );
			await editor.saveSiteEditorEntities( {
				isOnlyCurrentEntityDirty: true,
			} );

			// Verify template name didn't change.
			// See: https://github.com/woocommerce/woocommerce/issues/42221
			await expect(
				page.getByRole( 'heading', {
					name: templateTypeName,
				} )
			).toBeVisible();

			await testData.visitPage( {
				admin,
				editor,
				frontendUtils,
				requestUtils,
				page,
			} );
			await expect( page.getByText( userText ).first() ).toBeVisible();

			// Verify the edition can be reverted.
			await admin.visitSiteEditor( {
				postType: testData.templateType,
			} );
			await editor.revertTemplate( {
				templateName: testData.templateName,
			} );
			await testData.visitPage( {
				admin,
				editor,
				frontendUtils,
				requestUtils,
				page,
			} );
			await expect( page.getByText( userText ) ).toBeHidden();
		} );

		if ( testData.fallbackTemplate ) {
			test( `"${ testData.templateName }" template defaults to the "${ testData.fallbackTemplate.templateName }" template`, async ( {
				admin,
				frontendUtils,
				requestUtils,
				editor,
				page,
			} ) => {
				const templateSlug =
					testData.templateType === 'wp_template'
						? BLOCK_THEME_SLUG
						: 'woocommerce/woocommerce';
				// Edit fallback template and verify changes are visible.
				await admin.visitSiteEditor( {
					postId: `${ templateSlug }//${ testData.fallbackTemplate?.templatePath }`,
					postType: testData.templateType,
					canvas: 'edit',
				} );

				await editor.canvas
					.locator( 'body' )
					.waitFor( { timeout: 20000 } );

				await editor.insertBlock( {
					name: 'core/paragraph',
					attributes: {
						content: fallbackTemplateUserText,
					},
				} );
				await editor.saveSiteEditorEntities( {
					isOnlyCurrentEntityDirty: true,
				} );
				await testData.visitPage( {
					admin,
					editor,
					frontendUtils,
					requestUtils,
					page,
				} );
				await expect(
					page.getByText( fallbackTemplateUserText ).first()
				).toBeVisible();

				// Verify the edition can be reverted.
				await admin.visitSiteEditor( {
					postType: testData.templateType,
				} );

				await editor.revertTemplate( {
					templateName: testData.fallbackTemplate?.templateName || '',
				} );

				await testData.visitPage( {
					admin,
					editor,
					frontendUtils,
					requestUtils,
					page,
				} );
				await expect(
					page.getByText( fallbackTemplateUserText )
				).toBeHidden();
			} );
		}
	} );

	// Note: `wp_template` hierarchy is tested in `template-priority.block_theme.spec.ts`.
	const testToRun = CUSTOMIZABLE_WC_TEMPLATES.filter(
		( data ) =>
			data.templateType === 'wp_template_part' &&
			data.canBeOverriddenByThemes
	);

	for ( const testData of testToRun ) {
		const userText = `Hello World in the ${ testData.templateName } template`;
		const woocommerceTemplateUserText = `Hello World in the WooCommerce ${ testData.templateName } template`;

		test( `user-modified "${ testData.templateName }" template based on the theme template has priority over the user-modified template based on the default WooCommerce template`, async ( {
			page,
			admin,
			editor,
			requestUtils,
			frontendUtils,
		} ) => {
			await customizeTemplatePartViaRest(
				requestUtils,
				`woocommerce/woocommerce//${ testData.templatePath }`,
				woocommerceTemplateUserText
			);

			await requestUtils.activateTheme( BLOCK_THEME_WITH_TEMPLATES_SLUG );

			// Edit the theme template. The theme template is not
			// directly available from the UI, because the customized
			// one takes priority, so we go directly to its URL.
			await admin.visitSiteEditor( {
				postId: `${ BLOCK_THEME_WITH_TEMPLATES_SLUG }//${ testData.templatePath }`,
				postType: testData.templateType,
				canvas: 'edit',
			} );

			await editor.canvas.locator( 'body' ).waitFor( { timeout: 20000 } );

			await editor.insertBlock( {
				name: 'core/paragraph',
				attributes: { content: userText },
			} );
			await editor.saveSiteEditorEntities( {
				isOnlyCurrentEntityDirty: true,
			} );

			// Verify the template is the one modified by the user based on the theme.
			await testData.visitPage( {
				admin,
				editor,
				frontendUtils,
				requestUtils,
				page,
			} );
			await expect( page.getByText( userText ).first() ).toBeVisible();
			await expect(
				page.getByText( woocommerceTemplateUserText )
			).toBeHidden();

			// Revert edition and verify the user-modified WC template is used.
			// Note: we need to revert it from the admin (instead of calling
			// `deleteAllTemplates()`). This way, we verify there are no
			// duplicate templates with the same name.
			// See: https://github.com/woocommerce/woocommerce/issues/42220
			await admin.visitSiteEditor( {
				postType: testData.templateType,
			} );

			await editor.revertTemplate( {
				templateName: testData.templateName,
			} );

			await testData.visitPage( {
				admin,
				editor,
				frontendUtils,
				requestUtils,
				page,
			} );

			await expect(
				page.getByText( woocommerceTemplateUserText ).first()
			).toBeVisible();
			await expect( page.getByText( userText ) ).toBeHidden();
		} );
	}
} );
