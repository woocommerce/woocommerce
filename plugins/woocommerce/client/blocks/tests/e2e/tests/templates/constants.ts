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
		visitPage: async ( { admin, editor, page } ) => {
			// We will be able to simplify this logic once the blockified
			// Add to Cart with Options block is the default.
			await admin.visitSiteEditor( {
				postId: 'woocommerce/woocommerce//single-product',
				postType: 'wp_template',
				canvas: 'edit',
			} );
			await editor.insertBlock( {
				name: 'woocommerce/add-to-cart-with-options',
			} );
			await editor.saveSiteEditorEntities( {
				isOnlyCurrentEntityDirty: true,
			} );

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
		visitPage: async ( { frontendUtils, page } ) => {
			const checkoutPage = new CheckoutPage( { page } );
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
