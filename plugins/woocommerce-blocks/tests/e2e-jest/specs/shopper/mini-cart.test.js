/**
 * External dependencies
 */
import { setDefaultOptions, getDefaultOptions } from 'expect-puppeteer';
import { default as WooCommerceRestApi } from '@woocommerce/woocommerce-rest-api';
import { SHOP_PAGE, SHOP_CART_PAGE } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { shopper } from '../../../utils';
import { merchant } from '../../../utils/merchant';
import { getTextContent } from '../../page-utils';
import { SHOP_CHECKOUT_BLOCK_PAGE, useTheme } from '../../utils';

const block = {
	name: 'Mini-Cart',
	selectors: {
		frontend: {
			productWithAddToCartButton:
				'.wc-block-grid__product:nth-child(2) .add_to_cart_button',
			productTitle:
				'.wc-block-grid__product:nth-child(2) .wc-block-components-product-name',
			productPrice:
				'.wc-block-grid__product:nth-child(2) .wc-block-grid__product-price',
			addToCartButton: 'button.add_to_cart_button',
			checkoutOrderSummary: {
				content: '.wc-block-components-order-summary__content',
				toggle: '.wc-block-components-order-summary button[aria-expanded="false"]',
			},
		},
	},
};

const { selectors } = block;

const options = getDefaultOptions();

const clickMiniCartButton = async () => {
	await page.hover( '.wc-block-mini-cart__button' );

	await page.waitForSelector( '.wc-block-mini-cart__drawer.is-loading', {
		hidden: true,
	} );

	await page.click( '.wc-block-mini-cart__button' );
};

const closeMiniCartDrawer = async () => {
	await page.keyboard.press( 'Escape' );
};

/**
 * ConsumerKey and ConsumerSecret are not used, we use basic auth, but
 * not providing them will throw an error.
 */
const WooCommerce = new WooCommerceRestApi( {
	url: `${ process.env.WORDPRESS_BASE_URL }/`,
	consumerKey: 'consumer_key',
	consumerSecret: 'consumer_secret',
	version: 'wc/v3',
	axiosConfig: {
		auth: {
			username: process.env.WORDPRESS_LOGIN,
			password: process.env.WORDPRESS_PASSWORD,
		},
	},
} );

if ( process.env.WOOCOMMERCE_BLOCKS_PHASE < 2 ) {
	// Skips all the tests if it's a WooCommerce Core process environment.
	// eslint-disable-next-line jest/no-focused-tests, jest/expect-expect
	test.only( `Skipping ${ block.name } tests`, () => {} );
}

describe( 'Shopper → Mini-Cart', () => {
	beforeAll( async () => {
		/**
		 * Mini-Cart takes time to open. Sometimes, on slow machines, 500ms
		 * is not enough. So, we increase the default timeout to 5 seconds.
		 */
		setDefaultOptions( { ...options, timeout: 5000 } );
	} );

	afterAll( async () => {
		// Reset default options.
		setDefaultOptions( options );
	} );

	beforeEach( async () => {
		await shopper.block.goToBlockPage( block.name );
	} );

	describe( 'Icon', () => {
		it( 'Shopper can see the Mini-Cart icon and it badge on the front end', async () => {
			await expect( page ).toMatchElement( '.wc-block-mini-cart' );
			await expect( page ).toMatchElement(
				'.wc-block-mini-cart__button'
			);
			await expect( page ).toMatchElement(
				'.wc-block-mini-cart__quantity-badge'
			);
			await expect( page ).toMatchElement( '.wc-block-mini-cart__badge', {
				text: '',
			} );
		} );
	} );

	describe( 'Empty Mini-Cart', () => {
		it( 'When the cart is empty, the Mini-Cart Drawer show empty cart message and start shopping button', async () => {
			await clickMiniCartButton();

			await expect( page ).toMatchElement(
				'.wc-block-mini-cart__drawer',
				{
					text: 'Your cart is currently empty!',
				}
			);

			await expect( page ).toMatchElement(
				'.wc-block-mini-cart__drawer',
				{
					text: 'Start shopping',
				}
			);

			const shopLink = await page.$eval(
				'a.wc-block-mini-cart__shopping-button',
				( el ) => el.href
			);

			expect( shopLink ).toMatch( SHOP_PAGE );
		} );
	} );

	describe.skip( 'Filled Mini-Cart', () => {
		beforeAll( async () => {
			await shopper.block.emptyCart();
		} );

		afterEach( async () => {
			await shopper.block.emptyCart();
		} );

		it( 'The Mini-Cart title shows correct amount', async () => {
			await page.click( selectors.frontend.productWithAddToCartButton );

			await expect( page ).toMatchElement( '.wc-block-mini-cart__title', {
				text: 'Your cart (1 item)',
			} );

			await closeMiniCartDrawer();

			await page.click(
				'.wc-block-grid__product:last-child .add_to_cart_button'
			);

			await expect( page ).toMatchElement( '.wc-block-mini-cart__title', {
				text: 'Your cart (2 items)',
			} );
		} );

		it( 'The Mini-Cart products table show added products', async () => {
			const products = await page.$$(
				'.wc-block-all-products .wc-block-grid__product'
			);

			if ( products.length === 0 ) {
				throw new Error(
					'No products found on the Mini-Cart Block page.'
				);
			}

			const product = products[ 1 ];
			const [ productTitle ] = await getTextContent(
				'.wc-block-components-product-name',
				product
			);
			const addToCartButton = await product.$( '.add_to_cart_button' );

			await addToCartButton.click();

			await expect( page ).toMatchElement(
				'.wc-block-mini-cart__products-table',
				{
					text: productTitle,
				}
			);
		} );

		it( 'Filled Mini-Cart footer contains subtotal, view cart button, and go to checkout buttons', async () => {
			await page.click( selectors.frontend.addToCartButton );

			await expect( page ).toMatchElement( '.wc-block-mini-cart__title', {
				text: 'Your cart',
			} );

			await expect( page ).toMatchElement(
				'.wc-block-mini-cart__footer',
				{
					text: 'Subtotal',
				}
			);

			await expect( page ).toMatchElement(
				'.wc-block-mini-cart__footer-cart',
				{
					text: 'View my cart',
				}
			);

			await expect( page ).toMatchElement(
				'.wc-block-mini-cart__footer-checkout',
				{
					text: 'Go to checkout',
				}
			);
		} );
	} );

	describe.skip( 'Update quantity', () => {
		beforeAll( async () => {
			await shopper.block.emptyCart();
		} );

		afterEach( async () => {
			await shopper.block.emptyCart();
		} );

		it( 'The quantity of a product can be updated using plus and minus button', async () => {
			await page.click( selectors.frontend.productWithAddToCartButton );

			await expect( page ).toMatchElement( '.wc-block-mini-cart__title', {
				text: 'Your cart (1 item)',
			} );

			await page.waitForSelector(
				'.wc-block-components-quantity-selector__button--plus'
			);

			await page.click(
				'.wc-block-components-quantity-selector__button--plus'
			);

			await expect( page ).toMatchElement( '.wc-block-mini-cart__title', {
				text: 'Your cart (2 items)',
			} );

			await page.click(
				'.wc-block-components-quantity-selector__button--plus'
			);
			await page.click(
				'.wc-block-components-quantity-selector__button--plus'
			);
			await page.click(
				'.wc-block-components-quantity-selector__button--plus'
			);

			await expect( page ).toMatchElement( '.wc-block-mini-cart__title', {
				text: 'Your cart (5 items)',
			} );

			await page.click(
				'.wc-block-components-quantity-selector__button--minus'
			);

			await expect( page ).toMatchElement( '.wc-block-mini-cart__title', {
				text: 'Your cart (4 items)',
			} );
		} );

		it( 'Minus button is disabled if product quantity is 1', async () => {
			await page.click( selectors.frontend.productWithAddToCartButton );

			await expect( page ).toMatchElement( '.wc-block-mini-cart__title', {
				text: 'Your cart (1 item)',
			} );

			await page.waitForTimeout( 500 );

			expect(
				await page.$(
					'button.wc-block-components-quantity-selector__button--minus[disabled]'
				)
			).toBeTruthy();

			await page.click(
				'.wc-block-components-quantity-selector__button--plus'
			);

			await expect( page ).toMatchElement( '.wc-block-mini-cart__title', {
				text: 'Your cart (2 items)',
			} );

			expect(
				await page.$(
					'button.wc-block-components-quantity-selector__button--minus[disabled]'
				)
			).toBeFalsy();

			await page.click(
				'.wc-block-components-quantity-selector__button--minus'
			);

			await expect( page ).toMatchElement( '.wc-block-mini-cart__title', {
				text: 'Your cart (1 item)',
			} );

			expect(
				await page.$(
					'button.wc-block-components-quantity-selector__button--minus[disabled]'
				)
			).toBeTruthy();
		} );
	} );

	describe.skip( 'Tax included', () => {
		let taxSettings;
		beforeAll( async () => {
			taxSettings = ( await WooCommerce.get( 'settings/tax' ) ).data;
			/**
			 * Set the tax display settings to show prices including tax during
			 * cart and checkout. The price displayed in the product loop are
			 * tax excluded.
			 */
			await WooCommerce.post( 'settings/tax/batch', {
				update: [
					{
						id: 'woocommerce_tax_display_shop',
						value: 'excl',
					},
					{
						id: 'woocommerce_tax_display_cart',
						value: 'incl',
					},
				],
			} );
			await shopper.block.emptyCart();
		} );

		afterAll( async () => {
			const displayShop = taxSettings.find(
				( setting ) => setting.id === 'woocommerce_tax_display_shop'
			);
			const displayCart = taxSettings.find(
				( setting ) => setting.id === 'woocommerce_tax_display_cart'
			);
			await WooCommerce.post( 'settings/tax/batch', {
				update: [
					{
						id: 'woocommerce_tax_display_shop',
						value: displayShop.value,
					},
					{
						id: 'woocommerce_tax_display_cart',
						value: displayCart.value,
					},
				],
			} );
			await shopper.block.emptyCart();
		} );

		it( 'Mini-Cart show tax label and price including tax', async () => {
			const [ priceInLoop ] = await getTextContent(
				selectors.frontend.productPrice
			);

			await page.click( selectors.frontend.productWithAddToCartButton );

			await expect( page ).toMatchElement( '.wc-block-mini-cart__title', {
				text: 'Your cart (1 item)',
			} );

			await page.waitForSelector( '.wc-block-cart-item__prices' );
			const [ priceInCart ] = await getTextContent(
				'.wc-block-cart-item__prices'
			);

			expect( priceInLoop ).not.toMatch( priceInCart );

			await closeMiniCartDrawer();

			const [ priceInMiniCartButton ] = await getTextContent(
				'.wc-block-mini-cart__amount'
			);

			expect( priceInLoop ).not.toMatch( priceInMiniCartButton );
			expect( priceInCart ).toMatch( priceInMiniCartButton );

			await expect( page ).toMatchElement(
				'.wc-block-mini-cart__button',
				{
					text: '(incl.',
				}
			);
		} );
	} );

	describe.skip( 'Remove product', () => {
		beforeAll( async () => {
			await shopper.block.emptyCart();
		} );

		afterAll( async () => {
			await shopper.block.emptyCart();
		} );

		it( 'Can remove product from Mini-Cart', async () => {
			await page.click( selectors.frontend.addToCartButton );

			await expect( page ).toMatchElement( '.wc-block-mini-cart__title', {
				text: 'Your cart (1 item)',
			} );

			await page.waitForTimeout( 500 ); // Ensure the drawer is fully opened.

			await page.click( '.wc-block-cart-item__remove-link' );

			await expect( page ).toMatchElement(
				'.wc-block-mini-cart__drawer',
				{
					text: 'Your cart is currently empty!',
				}
			);
		} );
	} );

	describe.skip( 'Cart page', () => {
		beforeAll( async () => {
			await shopper.block.emptyCart();
		} );

		it( 'Can go to cart page from the Mini-Cart Footer', async () => {
			const [ productTitle ] = await getTextContent(
				selectors.frontend.productTitle
			);

			await page.click( selectors.frontend.productWithAddToCartButton );

			await expect( page ).toMatchElement(
				'.wc-block-mini-cart__products-table',
				{
					text: productTitle,
				}
			);

			const cartUrl = await page.$eval(
				'.wc-block-mini-cart__footer-cart',
				( el ) => el.href
			);

			expect( cartUrl ).toMatch( SHOP_CART_PAGE );

			await page.goto( cartUrl, { waitUntil: 'networkidle0' } );

			await expect( page ).toMatchElement( 'h1', { text: 'Cart' } );

			await expect( page ).toMatch( productTitle );
		} );
	} );

	describe.skip( 'Checkout page', () => {
		beforeAll( async () => {
			await shopper.block.emptyCart();
		} );

		it( 'Can go to checkout page from the Mini-Cart Footer', async () => {
			const productTitle = await page.$eval(
				selectors.frontend.productTitle,
				( el ) => el.textContent
			);

			await page.click( selectors.frontend.productWithAddToCartButton );

			await expect( page ).toMatchElement(
				'.wc-block-mini-cart__products-table',
				{
					text: productTitle,
				}
			);

			const checkoutUrl = await page.$eval(
				'.wc-block-mini-cart__footer-checkout',
				( el ) => el.href
			);

			expect( checkoutUrl ).toMatch( SHOP_CHECKOUT_BLOCK_PAGE );

			await page.goto( checkoutUrl, { waitUntil: 'networkidle0' } );

			await expect( page ).toMatchElement( 'h1', { text: 'Checkout' } );

			const orderSummaryToggle = await page.$(
				selectors.frontend.checkoutOrderSummary.toggle
			);

			if ( orderSummaryToggle ) {
				await orderSummaryToggle.click();
			}

			await expect( page ).toMatchElement(
				selectors.frontend.checkoutOrderSummary.content,
				{ text: productTitle }
			);
		} );
	} );

	describe.skip( 'Translations', () => {
		beforeAll( async () => {
			await merchant.changeLanguage( 'nl_NL' );
			await shopper.block.emptyCart();
		} );

		beforeEach( async () => {
			await shopper.block.goToBlockPage( block.name );
		} );

		afterAll( async () => {
			await merchant.changeLanguage( '' );
		} );

		describe( 'Classic Themes', () => {
			afterAll( async () => {
				await shopper.block.emptyCart();
			} );

			it( 'User can see translation in empty Mini-Cart', async () => {
				await clickMiniCartButton();

				await expect( page ).toMatchElement(
					'.wc-block-mini-cart__drawer',
					{
						text: 'Begin met winkelen',
					}
				);
			} );

			it.skip( 'User can see translation in filled Mini-Cart', async () => {
				await page.click(
					selectors.frontend.productWithAddToCartButton
				);

				await expect( page ).toMatchElement(
					'.wc-block-mini-cart__footer-cart',
					{
						text: 'Bekijk mijn winkelwagen',
					}
				);
			} );
		} );

		describe( 'Block Themes', () => {
			useTheme( 'twentytwentytwo' );

			afterAll( async () => {
				await shopper.block.emptyCart();
			} );

			it( 'User can see translation in empty Mini-Cart', async () => {
				await clickMiniCartButton();

				await expect( page ).toMatchElement(
					'.wc-block-mini-cart__drawer',
					{
						text: 'Begin met winkelen',
					}
				);
			} );

			it.skip( 'User can see translation in filled Mini-Cart', async () => {
				await page.click(
					selectors.frontend.productWithAddToCartButton
				);

				await expect( page ).toMatchElement(
					'.wc-block-mini-cart__footer-cart',
					{
						text: 'Bekijk mijn winkelwagen',
					}
				);
			} );
		} );
	} );
} );
