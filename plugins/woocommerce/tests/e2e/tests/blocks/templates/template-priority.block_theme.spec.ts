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

type TemplateResponse = {
	id: string;
	wp_id: number;
	content: { raw: string };
};

const markerCount = ( content: string, marker: string ) =>
	content.split( marker ).length - 1;

const getTemplate = async (
	requestUtils: RequestUtils,
	id: string
): Promise< TemplateResponse > =>
	requestUtils.rest< TemplateResponse >( {
		method: 'GET',
		path: `/wp/v2/templates/${ id }?context=edit`,
	} );

const assertPluginBase = (
	template: TemplateResponse,
	expectedId: string,
	marker: string
) => {
	if (
		template.id !== expectedId ||
		template.content.raw.length === 0 ||
		markerCount( template.content.raw, marker ) !== 0
	) {
		throw new Error(
			`Template did not start from the expected plugin base: ${ template.id }`
		);
	}
};

const assertCustomTemplate = (
	template: TemplateResponse,
	expectedId: string,
	expectedContent: string,
	marker: string
) => {
	if (
		template.id !== expectedId ||
		! Number.isInteger( template.wp_id ) ||
		template.wp_id <= 0 ||
		template.content.raw !== expectedContent ||
		markerCount( template.content.raw, marker ) !== 1
	) {
		throw new Error(
			`Template customization did not match the requested state: ${ template.id }`
		);
	}
};

const customizeTemplateViaRest = async (
	requestUtils: RequestUtils,
	id: string,
	marker: string
) => {
	const base = await getTemplate( requestUtils, id );
	assertPluginBase( base, id, marker );

	const expectedContent = `${ base.content.raw }
<!-- wp:paragraph -->
<p>${ marker }</p>
<!-- /wp:paragraph -->`;
	const response = await requestUtils.rest< TemplateResponse >( {
		method: 'PUT',
		path: `/wp/v2/templates/${ id }?context=edit`,
		data: {
			content: expectedContent,
		},
	} );
	const saved = await getTemplate( requestUtils, id );

	assertCustomTemplate( response, id, expectedContent, marker );
	assertCustomTemplate( saved, id, expectedContent, marker );

	if ( saved.id !== response.id || saved.wp_id !== response.wp_id ) {
		throw new Error(
			`Template customization identity changed after saving: ${ id }`
		);
	}

	return { base, saved };
};

const assertTemplateRestored = async (
	requestUtils: RequestUtils,
	base: TemplateResponse,
	marker: string
) => {
	const restored = await getTemplate( requestUtils, base.id );

	if (
		restored.id !== base.id ||
		restored.content.raw !== base.content.raw ||
		markerCount( restored.content.raw, marker ) !== 0
	) {
		throw new Error(
			`Template was not restored to its original base: ${ base.id }`
		);
	}
};

const assertCoexistingTemplates = async (
	requestUtils: RequestUtils,
	first: TemplateResponse,
	firstMarker: string,
	second: TemplateResponse,
	secondMarker: string
) => {
	const savedFirst = await getTemplate( requestUtils, first.id );
	const savedSecond = await getTemplate( requestUtils, second.id );

	if (
		savedFirst.id !== first.id ||
		savedSecond.id !== second.id ||
		! Number.isInteger( savedFirst.wp_id ) ||
		savedFirst.wp_id <= 0 ||
		! Number.isInteger( savedSecond.wp_id ) ||
		savedSecond.wp_id <= 0 ||
		savedFirst.wp_id !== first.wp_id ||
		savedSecond.wp_id !== second.wp_id ||
		savedFirst.id === savedSecond.id ||
		savedFirst.wp_id === savedSecond.wp_id ||
		savedFirst.content.raw !== first.content.raw ||
		savedSecond.content.raw !== second.content.raw ||
		markerCount( savedFirst.content.raw, firstMarker ) !== 1 ||
		markerCount( savedSecond.content.raw, secondMarker ) !== 1
	) {
		throw new Error( 'Template fallback customizations did not coexist' );
	}
};

test.describe( 'Template priority', () => {
	// Templates might come from different sources, and they should have this order of priority:
	// 1. Template from the database with the theme slug.
	// 2. Template from the database with the WooCommerce slug.
	// 3. Fallback template from the database with the theme slug.
	// 4. Fallback template from the database with the WooCommerce slug.
	// 5. Template from the theme.
	// 6. Fallback template from the theme.
	// 7. Template from WooCommerce.

	// We test a regular template and a taxonomy template with fallback, as they follow slightly different flow.
	const templatesToTest = [
		{
			path: '/product/hoodie',
			templateName: 'Single Product',
			templatePath: 'single-product',
			identifiableText: 'Related products',
		},
		{
			path: '/product-category/clothing',
			templateName: 'Products by Category',
			templatePath: 'taxonomy-product_cat',
			fallbackTemplate: {
				templateName: 'Product Catalog',
				templatePath: 'archive-product',
			},
			isTaxonomyTemplate: true,
			identifiableText: 'Showing all 9 results',
		},
	];

	templatesToTest.forEach( ( testData ) => {
		test( `priorities are applied correctly in the ${ testData.templateName } template`, async ( {
			admin,
			editor,
			page,
			requestUtils,
		} ) => {
			let wooCommerceTemplateBase: TemplateResponse | undefined;
			let wooCommerceTemplate: TemplateResponse | undefined;

			await test.step( 'WooCommerce template', async () => {
				await page.goto( testData.path );

				// Verify it loaded correctly but has no custom text.
				await expect(
					page.getByText( testData.identifiableText )
				).toBeVisible();
				await expect(
					page.getByText( 'Custom template' )
				).toBeHidden();
			} );

			await test.step( 'theme template', async () => {
				await requestUtils.activateTheme(
					BLOCK_THEME_WITH_TEMPLATES_SLUG
				);

				await page.goto( testData.path );

				await expect(
					page.getByText( testData.identifiableText )
				).toBeVisible();
				await expect(
					page.getByText( 'Custom template' )
				).toBeHidden();
				await requestUtils.activateTheme( BLOCK_THEME_SLUG );
			} );

			if ( testData.fallbackTemplate ) {
				await test.step( 'custom fallback template with WooCommerce slug', async () => {
					const customized = await customizeTemplateViaRest(
						requestUtils,
						`woocommerce/woocommerce//${ testData.fallbackTemplate.templatePath }`,
						'Custom fallback template with WooCommerce slug'
					);
					wooCommerceTemplate = customized.saved;

					await page.goto( testData.path );

					await expect(
						page.getByText( testData.identifiableText )
					).toBeVisible();
					await expect(
						page.getByText(
							'Custom fallback template with WooCommerce slug'
						)
					).toBeVisible();
				} );

				await test.step( 'custom fallback template with theme slug', async () => {
					const customized = await customizeTemplateViaRest(
						requestUtils,
						`${ BLOCK_THEME_SLUG }//${ testData.fallbackTemplate.templatePath }`,
						'Custom fallback template with theme slug'
					);

					if ( ! wooCommerceTemplate ) {
						throw new Error(
							'WooCommerce fallback template was not customized'
						);
					}

					await assertCoexistingTemplates(
						requestUtils,
						wooCommerceTemplate,
						'Custom fallback template with WooCommerce slug',
						customized.saved,
						'Custom fallback template with theme slug'
					);

					await page.goto( testData.path );

					await expect(
						page.getByText( testData.identifiableText )
					).toBeVisible();
					await expect(
						page.getByText(
							'Custom fallback template with theme slug'
						)
					).toBeVisible();
					await expect(
						page.getByText(
							'Custom fallback template with WooCommerce slug'
						)
					).toBeHidden();
				} );
			}

			// Note: we skip taxonomy templates because since
			// https://github.com/woocommerce/woocommerce/pull/62018
			// it's no longer possible to create those templates using the
			// WooCommerce slug.
			// Since https://github.com/woocommerce/woocommerce/pull/60191, the
			// only way to achieve that was hardcoding `woocommerce/woocommerce//`
			// to the URL. So we are only testing for backwards-compatibility.
			if (
				! ( 'isTaxonomyTemplate' in testData ) ||
				! testData.isTaxonomyTemplate
			) {
				await test.step( 'custom template with WooCommerce slug', async () => {
					const customized = await customizeTemplateViaRest(
						requestUtils,
						`woocommerce/woocommerce//${ testData.templatePath }`,
						'Custom template with WooCommerce slug'
					);
					wooCommerceTemplateBase = customized.base;

					await page.goto( testData.path );

					await expect(
						page.getByText( testData.identifiableText )
					).toBeVisible();
					await expect(
						page.getByText(
							'Custom fallback template with theme slug'
						)
					).toBeHidden();
					await expect(
						page.getByText(
							'Custom template with WooCommerce slug'
						)
					).toBeVisible();
				} );
			}

			await admin.visitSiteEditor( {
				postType: 'wp_template',
			} );
			if (
				! ( 'isTaxonomyTemplate' in testData ) ||
				! testData.isTaxonomyTemplate
			) {
				await editor.revertTemplate( {
					templateName: testData.templateName,
				} );

				if ( ! wooCommerceTemplateBase ) {
					throw new Error(
						'WooCommerce template base was not captured'
					);
				}

				await assertTemplateRestored(
					requestUtils,
					wooCommerceTemplateBase,
					'Custom template with WooCommerce slug'
				);
			}

			await test.step( 'custom template with theme slug', async () => {
				if ( testData.fallbackTemplate ) {
					await editor.createTemplate( {
						templateName: testData.templateName,
					} );

					// Verify we are editing the correct template.
					await page
						.getByRole( 'heading', {
							name: `${ testData.templateName } · Template`,
							level: 1,
						} )
						.waitFor();

					await editor.insertBlock( {
						name: 'core/paragraph',
						attributes: {
							content: 'Custom template with theme slug',
						},
					} );

					await editor.saveSiteEditorEntities( {
						isOnlyCurrentEntityDirty: true,
					} );
				} else {
					await customizeTemplateViaRest(
						requestUtils,
						`${ BLOCK_THEME_SLUG }//${ testData.templatePath }`,
						'Custom template with theme slug'
					);
				}

				await page.goto( testData.path );

				await expect(
					page.getByText( testData.identifiableText )
				).toBeVisible();
				await expect(
					page.getByText( 'Custom template with theme slug' )
				).toBeVisible();
				await expect(
					page.getByText( 'Custom template with WooCommerce slug' )
				).toBeHidden();
			} );
		} );
	} );
} );
