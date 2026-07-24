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

type TemplateContentResponse = {
	id: string;
	wp_id: number;
	content: { raw: string };
};

const SINGLE_PRODUCT_TEMPLATE_ID = 'woocommerce/woocommerce//single-product';
const ADD_TO_CART_WITH_OPTIONS_BLOCK =
	'<!-- wp:woocommerce/add-to-cart-with-options /-->';
const ADD_TO_CART_WITH_OPTIONS_BLOCK_START =
	'<!-- wp:woocommerce/add-to-cart-with-options';

const blockCount = ( content: string ) =>
	content.split( ADD_TO_CART_WITH_OPTIONS_BLOCK_START ).length - 1;

const getSingleProductTemplate = async (
	requestUtils: RequestUtils
): Promise< TemplateContentResponse > =>
	requestUtils.rest< TemplateContentResponse >( {
		method: 'GET',
		path: `/wp/v2/templates/${ SINGLE_PRODUCT_TEMPLATE_ID }?context=edit`,
	} );

const ensureAddToCartWithOptionsOnSingleProduct = async (
	requestUtils: RequestUtils
) => {
	const template = await getSingleProductTemplate( requestUtils );
	if (
		template.id !== SINGLE_PRODUCT_TEMPLATE_ID ||
		typeof template.content?.raw !== 'string' ||
		template.content.raw.length === 0
	) {
		throw new Error( 'Single Product template content was unavailable' );
	}

	const currentBlockCount = blockCount( template.content.raw );
	if ( currentBlockCount === 1 ) {
		return;
	}
	if ( currentBlockCount > 1 ) {
		throw new Error(
			'Single Product template has duplicate prerequisites'
		);
	}

	const expectedContent = `${ template.content.raw }
${ ADD_TO_CART_WITH_OPTIONS_BLOCK }`;
	const saved = await requestUtils.rest< TemplateContentResponse >( {
		method: 'PUT',
		path: `/wp/v2/templates/${ SINGLE_PRODUCT_TEMPLATE_ID }?context=edit`,
		data: {
			content: expectedContent,
		},
	} );

	if (
		saved.id !== SINGLE_PRODUCT_TEMPLATE_ID ||
		! Number.isInteger( saved.wp_id ) ||
		saved.wp_id <= 0 ||
		saved.content.raw !== expectedContent
	) {
		throw new Error( 'Add to Cart + Options prerequisite was not saved' );
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
			await ensureAddToCartWithOptionsOnSingleProduct( requestUtils );

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
