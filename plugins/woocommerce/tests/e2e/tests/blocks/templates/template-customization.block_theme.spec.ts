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
	theme: string;
	slug: string;
	type: 'wp_template_part';
	status: 'publish';
	source: 'plugin' | 'custom';
	origin: 'plugin';
	original_source: 'plugin';
	author: number;
	has_theme_file: boolean;
	area: string;
	title: {
		raw: string;
		rendered: string;
	};
	description: string;
	content: {
		raw: string;
		block_version: number;
	};
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

const hasStableTemplatePartProjection = (
	actual: TemplatePartResponse,
	expected: TemplatePartResponse
) =>
	actual.id === expected.id &&
	actual.theme === expected.theme &&
	actual.slug === expected.slug &&
	actual.type === expected.type &&
	actual.status === expected.status &&
	actual.origin === expected.origin &&
	actual.original_source === expected.original_source &&
	actual.area === expected.area &&
	actual.title.raw === expected.title.raw &&
	actual.title.rendered === expected.title.rendered &&
	actual.description === expected.description &&
	actual.content.block_version === expected.content.block_version;

const assertPluginTemplatePartBase = (
	templatePart: TemplatePartResponse,
	expectedId: string,
	expectedArea: string,
	expectedTitle: string,
	marker: string
) => {
	const idSeparator = expectedId.lastIndexOf( '//' );
	const expectedSlug = expectedId.slice( idSeparator + 2 );

	if (
		idSeparator < 1 ||
		templatePart.id !== expectedId ||
		templatePart.theme !== 'woocommerce/woocommerce' ||
		templatePart.slug !== expectedSlug ||
		templatePart.type !== 'wp_template_part' ||
		templatePart.status !== 'publish' ||
		templatePart.source !== 'plugin' ||
		templatePart.origin !== 'plugin' ||
		templatePart.original_source !== 'plugin' ||
		templatePart.wp_id !== 0 ||
		templatePart.author !== 0 ||
		! templatePart.has_theme_file ||
		templatePart.area !== expectedArea ||
		templatePart.title.raw !== expectedTitle ||
		templatePart.title.rendered !== expectedTitle ||
		templatePart.description.length === 0 ||
		templatePart.content.block_version !== 1 ||
		templatePart.content.raw.length === 0 ||
		templatePart.content.raw.includes( marker )
	) {
		throw new Error(
			`Template part did not start from the expected plugin base: ${ templatePart.id }`
		);
	}
};

const assertCustomTemplatePart = (
	templatePart: TemplatePartResponse,
	base: TemplatePartResponse,
	expectedContent: string,
	marker: string
) => {
	if (
		! hasStableTemplatePartProjection( templatePart, base ) ||
		templatePart.source !== 'custom' ||
		! Number.isInteger( templatePart.wp_id ) ||
		templatePart.wp_id <= 0 ||
		templatePart.author !== 1 ||
		templatePart.has_theme_file ||
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
	area: string,
	title: string,
	marker: string
) => {
	const base = await getTemplatePart( requestUtils, id );
	assertPluginTemplatePartBase( base, id, area, title, marker );

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

	assertCustomTemplatePart( response, base, expectedContent, marker );
	assertCustomTemplatePart( saved, base, expectedContent, marker );

	if ( saved.wp_id !== response.wp_id ) {
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
	const expectedPriorityTemplateParts = [
		{
			templateName: 'Mini-Cart',
			templatePath: 'mini-cart',
			area: 'mini-cart',
		},
		{
			templateName: 'External Product Add to Cart + Options',
			templatePath: 'external-product-add-to-cart-with-options',
			area: 'add-to-cart-with-options',
		},
	];
	const hasExpectedPriorityTemplateParts =
		testToRun.length === expectedPriorityTemplateParts.length &&
		testToRun.every( ( testData, index ) => {
			const expected = expectedPriorityTemplateParts[ index ];

			return (
				expected &&
				testData.templateName === expected.templateName &&
				testData.templatePath === expected.templatePath &&
				testData.templateType === 'wp_template_part' &&
				testData.canBeOverriddenByThemes
			);
		} );

	if ( ! hasExpectedPriorityTemplateParts ) {
		throw new Error(
			'Expected Mini-Cart and External Product Add to Cart + Options priority template parts.'
		);
	}

	for ( const [ index, testData ] of testToRun.entries() ) {
		const userText = `Hello World in the ${ testData.templateName } template`;
		const woocommerceTemplateUserText = `Hello World in the WooCommerce ${ testData.templateName } template`;
		const expectedArea = expectedPriorityTemplateParts[ index ].area;

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
				expectedArea,
				testData.templateName,
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
