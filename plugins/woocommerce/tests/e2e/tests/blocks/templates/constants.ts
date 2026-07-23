/**
 * External dependencies
 */
import type { Page, Response } from '@playwright/test';
import type {
	Admin,
	Editor,
	FrontendUtils,
	RequestUtils,
} from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { SIMPLE_VIRTUAL_PRODUCT_NAME } from '../checkout/constants';
import { CheckoutPage } from '../checkout/checkout.page';

type TemplateCustomizationTest = {
	visitPage: ( props: {
		admin: Admin;
		editor: Editor;
		frontendUtils: FrontendUtils;
		requestUtils: RequestUtils;
		page: Page;
	} ) => Promise< void | Response | null >;
	templateName: string;
	templatePath: string;
	templateType: 'wp_template' | 'wp_template_part';
	fallbackTemplate?: {
		templateName: string;
		templatePath: string;
	};
	canBeOverriddenByThemes: boolean;
	isTaxonomyTemplate?: boolean;
};

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

const SINGLE_PRODUCT_TEMPLATE_ID = 'woocommerce/woocommerce//single-product';
const ADD_TO_CART_WITH_OPTIONS_BLOCK =
	'<!-- wp:woocommerce/add-to-cart-with-options /-->';
const ADD_TO_CART_WITH_OPTIONS_BLOCK_START =
	'<!-- wp:woocommerce/add-to-cart-with-options';
const singleProductTemplateState = new WeakMap< Page, TemplateResponse >();

const blockCount = ( content: string ) =>
	content.split( ADD_TO_CART_WITH_OPTIONS_BLOCK ).length - 1;
const blockStartCount = ( content: string ) =>
	content.split( ADD_TO_CART_WITH_OPTIONS_BLOCK_START ).length - 1;

const getSingleProductTemplate = async (
	requestUtils: RequestUtils
): Promise< TemplateResponse > =>
	requestUtils.rest< TemplateResponse >( {
		method: 'GET',
		path: `/wp/v2/templates/${ SINGLE_PRODUCT_TEMPLATE_ID }?context=edit`,
	} );

const hasStableTemplateProjection = (
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

const hasExpectedSingleProductIdentity = ( template: TemplateResponse ) =>
	template.id === SINGLE_PRODUCT_TEMPLATE_ID &&
	template.theme === 'woocommerce/woocommerce' &&
	template.slug === 'single-product' &&
	template.type === 'wp_template' &&
	template.status === 'publish' &&
	template.origin === 'plugin' &&
	template.original_source === 'plugin' &&
	template.plugin === 'woocommerce' &&
	template.title.raw === 'Single Product' &&
	template.title.rendered === 'Single Product' &&
	template.description.length > 0 &&
	template.content.block_version === 1 &&
	template.content.raw.length > 0;

const assertSingleProductPluginBase = ( template: TemplateResponse ) => {
	if (
		! hasExpectedSingleProductIdentity( template ) ||
		blockCount( template.content.raw ) !== 0 ||
		blockStartCount( template.content.raw ) !== 0 ||
		template.source !== 'plugin' ||
		template.wp_id !== 0 ||
		template.author !== 0 ||
		template.is_custom ||
		! template.has_theme_file
	) {
		throw new Error(
			`Single Product template did not start from the expected plugin base: ${ template.id }`
		);
	}
};

const assertFirstCandidateWrite = (
	template: TemplateResponse,
	firstWrite: TemplateResponse
) => {
	if (
		! hasExpectedSingleProductIdentity( template ) ||
		! hasStableTemplateProjection( template, firstWrite ) ||
		template.source !== 'custom' ||
		! Number.isInteger( template.wp_id ) ||
		template.wp_id <= 0 ||
		template.wp_id !== firstWrite.wp_id ||
		template.author !== 1 ||
		! template.is_custom ||
		template.has_theme_file ||
		template.content.raw !== firstWrite.content.raw ||
		blockCount( template.content.raw ) !== 1 ||
		blockStartCount( template.content.raw ) !== 1 ||
		! template.content.raw.endsWith(
			`\n${ ADD_TO_CART_WITH_OPTIONS_BLOCK }`
		)
	) {
		throw new Error(
			`Single Product template did not match the exact first write: ${ template.id }`
		);
	}
};

const assertExpectedSingleProductWrite = (
	template: TemplateResponse,
	input: TemplateResponse,
	expectedContent: string,
	expectedCount: number
) => {
	if (
		! hasStableTemplateProjection( template, input ) ||
		template.source !== 'custom' ||
		! Number.isInteger( template.wp_id ) ||
		template.wp_id <= 0 ||
		( input.wp_id > 0 && template.wp_id !== input.wp_id ) ||
		template.author !== 1 ||
		! template.is_custom ||
		template.has_theme_file ||
		template.content.raw !== expectedContent ||
		blockCount( template.content.raw ) !== expectedCount ||
		blockStartCount( template.content.raw ) !== expectedCount
	) {
		throw new Error(
			`Single Product template write did not match the expected progression: ${ template.id }`
		);
	}
};

const addToCartWithOptionsToSingleProduct = async (
	requestUtils: RequestUtils,
	page: Page
) => {
	const firstWrite = singleProductTemplateState.get( page );
	const input = await getSingleProductTemplate( requestUtils );

	if ( firstWrite ) {
		assertFirstCandidateWrite( input, firstWrite );
	} else {
		assertSingleProductPluginBase( input );
	}

	const inputCount = firstWrite ? 1 : 0;
	const expectedContent = `${ input.content.raw }
${ ADD_TO_CART_WITH_OPTIONS_BLOCK }`;
	const expectedCount = inputCount + 1;
	const response = await requestUtils.rest< TemplateResponse >( {
		method: 'PUT',
		path: `/wp/v2/templates/${ SINGLE_PRODUCT_TEMPLATE_ID }?context=edit`,
		data: {
			content: expectedContent,
		},
	} );
	const saved = await getSingleProductTemplate( requestUtils );

	assertExpectedSingleProductWrite(
		response,
		input,
		expectedContent,
		expectedCount
	);
	assertExpectedSingleProductWrite(
		saved,
		input,
		expectedContent,
		expectedCount
	);

	if ( saved.wp_id !== response.wp_id ) {
		throw new Error(
			'Single Product template identity changed after saving'
		);
	}

	if ( firstWrite ) {
		singleProductTemplateState.delete( page );
	} else {
		singleProductTemplateState.set( page, saved );
	}
};

export const CUSTOMIZABLE_WC_TEMPLATES: TemplateCustomizationTest[] = [
	{
		visitPage: async ( { frontendUtils } ) =>
			await frontendUtils.goToShop(),
		templateName: 'Product Catalog',
		templatePath: 'archive-product',
		templateType: 'wp_template',
		canBeOverriddenByThemes: true,
	},
	{
		visitPage: async ( { page } ) =>
			await page.goto( '/?s=shirt&post_type=product' ),
		templateName: 'Product Search Results',
		templatePath: 'product-search-results',
		templateType: 'wp_template',
		canBeOverriddenByThemes: true,
	},
	{
		visitPage: async ( { page } ) => await page.goto( '/color/blue' ),
		templateName: 'Products by Attribute',
		templatePath: 'taxonomy-product_attribute',
		templateType: 'wp_template',
		fallbackTemplate: {
			templateName: 'Product Catalog',
			templatePath: 'archive-product',
		},
		canBeOverriddenByThemes: true,
	},
	{
		visitPage: async ( { page } ) =>
			await page.goto( '/product-category/clothing' ),
		templateName: 'Products by Category',
		templatePath: 'taxonomy-product_cat',
		templateType: 'wp_template',
		fallbackTemplate: {
			templateName: 'Product Catalog',
			templatePath: 'archive-product',
		},
		canBeOverriddenByThemes: true,
		isTaxonomyTemplate: true,
	},
	{
		visitPage: async ( { page } ) =>
			await page.goto( '/product-tag/recommended/' ),
		templateName: 'Products by Tag',
		templatePath: 'taxonomy-product_tag',
		templateType: 'wp_template',
		fallbackTemplate: {
			templateName: 'Product Catalog',
			templatePath: 'archive-product',
		},
		canBeOverriddenByThemes: true,
		isTaxonomyTemplate: true,
	},
	{
		visitPage: async ( { page } ) => await page.goto( '/product/hoodie' ),
		templateName: 'Single Product',
		templatePath: 'single-product',
		templateType: 'wp_template',
		canBeOverriddenByThemes: true,
	},
	{
		visitPage: async ( { frontendUtils } ) => {
			await frontendUtils.goToShop();
			await frontendUtils.addToCart();
			const block = await frontendUtils.getBlockByName(
				'woocommerce/mini-cart'
			);
			await block.getByRole( 'button' ).click();
		},
		templateName: 'Mini-Cart',
		templatePath: 'mini-cart',
		templateType: 'wp_template_part',
		canBeOverriddenByThemes: true,
	},
	{
		visitPage: async ( { page, requestUtils } ) => {
			// We will be able to simplify this logic once the blockified
			// Add to Cart with Options block is the default.
			await addToCartWithOptionsToSingleProduct( requestUtils, page );

			await page.goto( '/product/wordpress-pennant/' );
		},
		templateName: 'External Product Add to Cart + Options',
		templatePath: 'external-product-add-to-cart-with-options',
		templateType: 'wp_template_part',
		canBeOverriddenByThemes: true,
	},
	{
		visitPage: async ( { frontendUtils } ) =>
			await frontendUtils.goToCart(),
		templateName: 'Page: Cart',
		templatePath: 'page-cart',
		templateType: 'wp_template',
		canBeOverriddenByThemes: true,
	},
	{
		visitPage: async ( { frontendUtils } ) => {
			await frontendUtils.goToShop();
			await frontendUtils.addToCart();
			await frontendUtils.goToCheckout();
		},
		templateName: 'Page: Checkout',
		templatePath: 'page-checkout',
		templateType: 'wp_template',
		canBeOverriddenByThemes: true,
	},
	{
		visitPage: async ( { frontendUtils } ) => {
			await frontendUtils.goToShop();
			await frontendUtils.addToCart();
			await frontendUtils.goToCheckout();
		},
		templateName: 'Checkout Header',
		templatePath: 'checkout-header',
		templateType: 'wp_template_part',
		// Creating a `checkout-header.html` template part in the theme doesn't
		// automatically override the checkout header. That's because the
		// Page: Checkout template still points to the default `checkout-header`
		// from WooCommerce.
		canBeOverriddenByThemes: false,
	},
	{
		visitPage: async ( { frontendUtils, page, requestUtils } ) => {
			const checkoutPage = new CheckoutPage( { page, requestUtils } );
			await frontendUtils.goToShop();
			await frontendUtils.addToCart( SIMPLE_VIRTUAL_PRODUCT_NAME );
			await frontendUtils.goToCheckout();
			await checkoutPage.fillInCheckoutWithTestData();
			await checkoutPage.placeOrder();
		},
		templateName: 'Order Confirmation',
		templatePath: 'order-confirmation',
		templateType: 'wp_template',
		canBeOverriddenByThemes: true,
	},
];

export const WC_TEMPLATES_SLUG = 'woocommerce/woocommerce';
