/**
 * External dependencies
 */
import { test, expect, wpCLI, type RequestUtils } from '@woocommerce/e2e-utils';

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
};

test.describe( 'Legacy templates', () => {
	test( 'woocommerce//* slug is supported', async ( {
		admin,
		page,
		editor,
		requestUtils,
	} ) => {
		const template = {
			id: 'single-product',
			name: 'Single Product',
			customText: 'This is a customized template.',
			frontendPath: '/product/hoodie/',
		};

		await test.step( 'Customize existing template to create DB entry', async () => {
			await customizeTemplateViaRest(
				requestUtils,
				`woocommerce/woocommerce//${ template.id }`,
				template.customText
			);

			await admin.visitSiteEditor( {
				postId: `woocommerce/woocommerce//${ template.id }`,
				postType: 'wp_template',
				canvas: 'edit',
			} );

			await expect(
				editor.canvas.getByText( template.customText )
			).toBeVisible();
		} );

		await test.step( 'Update created term to legacy format in the DB', async () => {
			await wpCLI(
				`term update wp_theme woocommerce-woocommerce \
					--by="slug" \
					--name="woocommerce" \
					--slug="woocommerce"`
			);
		} );

		await test.step( 'Verify the template can be edited via a legacy ID ', async () => {
			await admin.visitSiteEditor( {
				postId: `woocommerce//${ template.id }`,
				postType: 'wp_template',
				canvas: 'edit',
			} );

			await expect(
				editor.canvas.getByText( template.customText )
			).toBeVisible();
		} );

		await test.step( 'Verify the template is listed in the Site Editor UI', async () => {
			await admin.visitSiteEditor( {
				postType: 'wp_template',
			} );

			await page.getByPlaceholder( 'Search' ).fill( template.name );

			await expect(
				page.getByRole( 'button', { name: template.name } ).first()
			).toBeVisible();
		} );

		await test.step( 'Verify the template loads correctly in the frontend', async () => {
			await page.goto( template.frontendPath );

			await expect( page.getByText( template.customText ) ).toBeVisible();
		} );
	} );
} );
