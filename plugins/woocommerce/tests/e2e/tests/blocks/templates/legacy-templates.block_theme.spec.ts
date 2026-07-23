/**
 * External dependencies
 */
import { test, expect, wpCLI, type RequestUtils } from '@woocommerce/e2e-utils';

type TemplateResponse = {
	id: string;
	wp_id: number;
	theme: string;
	slug: string;
	type: 'wp_template';
	status: 'publish';
	source: 'plugin' | 'custom';
	origin: 'plugin';
	original_source: 'plugin';
	plugin: 'woocommerce';
	author: number;
	is_custom: boolean;
	has_theme_file: boolean;
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

const getTemplate = async (
	requestUtils: RequestUtils,
	id: string
): Promise< TemplateResponse > =>
	requestUtils.rest< TemplateResponse >( {
		method: 'GET',
		path: `/wp/v2/templates/${ id }?context=edit`,
	} );

const hasStableProjection = (
	actual: TemplateResponse,
	expected: TemplateResponse
) =>
	actual.id === expected.id &&
	actual.theme === expected.theme &&
	actual.slug === expected.slug &&
	actual.type === expected.type &&
	actual.status === expected.status &&
	actual.origin === expected.origin &&
	actual.original_source === expected.original_source &&
	actual.plugin === expected.plugin &&
	actual.title.raw === expected.title.raw &&
	actual.title.rendered === expected.title.rendered &&
	actual.description === expected.description &&
	actual.content.block_version === expected.content.block_version;

const assertPluginBase = (
	template: TemplateResponse,
	expectedId: string,
	marker: string
) => {
	if (
		template.id !== expectedId ||
		template.theme !== 'woocommerce/woocommerce' ||
		template.slug !== 'single-product' ||
		template.type !== 'wp_template' ||
		template.status !== 'publish' ||
		template.source !== 'plugin' ||
		template.origin !== 'plugin' ||
		template.original_source !== 'plugin' ||
		template.plugin !== 'woocommerce' ||
		template.wp_id !== 0 ||
		template.author !== 0 ||
		template.is_custom ||
		! template.has_theme_file ||
		template.content.block_version !== 1 ||
		template.content.raw.length === 0 ||
		template.content.raw.includes( marker )
	) {
		throw new Error(
			`Template did not start from the expected plugin base: ${ template.id }`
		);
	}
};

const assertCustomTemplate = (
	template: TemplateResponse,
	base: TemplateResponse,
	expectedContent: string,
	marker: string
) => {
	if (
		! hasStableProjection( template, base ) ||
		template.source !== 'custom' ||
		! Number.isInteger( template.wp_id ) ||
		template.wp_id <= 0 ||
		template.author !== 1 ||
		! template.is_custom ||
		template.has_theme_file ||
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

	assertCustomTemplate( response, base, expectedContent, marker );
	assertCustomTemplate( saved, base, expectedContent, marker );

	if ( saved.wp_id !== response.wp_id ) {
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
