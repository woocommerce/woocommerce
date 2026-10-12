/**
 * External dependencies
 */
import {
	addAProductToCart,
	WC_API_PATH,
	WP_API_PATH,
} from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { tags, test, expect } from '../../fixtures/fixtures';
import { checkCartContentInBlocksCart } from '../../utils/cart';

const productName = `Cart product test ${ Date.now() }`;
const productPrice = '13.99';

test.describe(
	'Add to Cart behavior',
	{ tag: [ tags.PAYMENTS, tags.SERVICES ] },
	() => {
		let productId: number;

		test.beforeAll( async ( { restApi } ) => {
			await restApi
				.post( `${ WC_API_PATH }/products`, {
					name: productName,
					type: 'simple',
					regular_price: productPrice,
				} )
				.then( ( response: { data: { id: number } } ) => {
					productId = response.data.id;
				} );
		} );

		test.beforeEach( async ( { context } ) => {
			// Shopping cart is very sensitive to cookies, so be explicit
			await context.clearCookies();
		} );

		test.afterAll( async ( { restApi } ) => {
			await restApi.post( `${ WC_API_PATH }/products/batch`, {
				delete: [ productId ],
			} );
		} );

		test( 'should show classic AJAX stock errors on the product list even when cart redirect is enabled', async ( {
			page,
			restApi,
		} ) => {
			const ajaxSetting = `${ WC_API_PATH }/settings/products/woocommerce_enable_ajax_add_to_cart`;
			const redirectSetting = `${ WC_API_PATH }/settings/products/woocommerce_cart_redirect_after_add`;
			const [ ajax, redirect ] = await Promise.all( [
				restApi.get( ajaxSetting ),
				restApi.get( redirectSetting ),
			] );
			const stockProductName = `Canvas bag ${ Date.now() }`;
			let stockProductId: number | undefined;
			let listPageId: number | undefined;

			try {
				const product = await restApi.post(
					`${ WC_API_PATH }/products`,
					{
						name: stockProductName,
						type: 'simple',
						regular_price: '12',
						manage_stock: true,
						stock_quantity: 1,
						backorders: 'no',
					}
				);
				stockProductId = product.data.id;
				// The products shortcode renders a classic product list on any theme.
				const listPage = await restApi.post( `${ WP_API_PATH }/pages`, {
					title: `Classic list ${ stockProductId }`,
					status: 'publish',
					content: `[products ids="${ stockProductId }"]`,
				} );
				listPageId = listPage.data.id;
				const listUrl = listPage.data.link;
				await restApi.put( ajaxSetting, { value: 'yes' } );
				await restApi.put( redirectSetting, { value: 'yes' } );
				await page.goto( listUrl );
				const addButton = page
					.locator( `.post-${ stockProductId }` )
					.getByRole( 'link', { name: /Add to cart/ } );
				const firstResponse = page.waitForResponse( ( response ) =>
					response.url().includes( 'wc-ajax=add_to_cart' )
				);
				await addButton.click();
				await firstResponse;
				await expect( page ).toHaveURL( /\/cart\// );
				await checkCartContentInBlocksCart(
					page,
					[
						{
							data: { name: stockProductName, price: '12' },
							qty: 1,
						},
					],
					12
				);

				await page.goto( listUrl );
				await page.evaluate( () => {
					document.body.dataset.addedToCartCount = '0';
					const jquery = (
						window as typeof window & {
							jQuery: ( element: HTMLElement ) => {
								on: (
									event: string,
									callback: () => void
								) => void;
							};
						}
					 ).jQuery;
					jquery( document.body ).on( 'added_to_cart', () => {
						document.body.dataset.addedToCartCount = String(
							Number( document.body.dataset.addedToCartCount ) + 1
						);
					} );
				} );
				const secondResponse = page.waitForResponse( ( response ) =>
					response.url().includes( 'wc-ajax=add_to_cart' )
				);
				await addButton.click();
				const secondResult = await ( await secondResponse ).json();
				expect( secondResult.error ).toBe( true );
				expect( typeof secondResult.messages ).toBe( 'string' );
				const errorAlert = page.getByRole( 'alert' ).filter( {
					hasText: 'You cannot add that amount to the cart',
				} );
				await expect( errorAlert ).toBeInViewport();
				await expect( page ).toHaveURL( listUrl );
				await expect( addButton ).not.toHaveClass( /loading|added/ );
				await expect( page.locator( 'body' ) ).toHaveAttribute(
					'data-added-to-cart-count',
					'0'
				);
			} finally {
				// Attempt every restoration even if another cleanup request fails.
				const cleanup = await Promise.allSettled( [
					restApi.put( ajaxSetting, { value: ajax.data.value } ),
					restApi.put( redirectSetting, {
						value: redirect.data.value,
					} ),
					...( listPageId
						? [
								restApi.delete(
									`${ WP_API_PATH }/pages/${ listPageId }`,
									{ force: true }
								),
							]
						: [] ),
					...( stockProductId
						? [
								restApi.delete(
									`${ WC_API_PATH }/products/${ stockProductId }`,
									{ force: true }
								),
							]
						: [] ),
				] );
				expect(
					cleanup.filter(
						( result ) => result.status === 'rejected'
					),
					'Every owned fixture and setting must be restored.'
				).toEqual( [] );
			}
		} );

		test(
			'should add only one product to the cart with AJAX add to cart buttons disabled and "Geolocate (with page caching support)" as the default customer location',
			{ tag: [ tags.COULD_BE_LOWER_LEVEL_TEST ] },
			async ( { page, restApi } ) => {
				// Set settings combination that allowed reproducing the bug.
				// @see https://github.com/woocommerce/woocommerce/issues/33077
				await restApi.put(
					`${ WC_API_PATH }/settings/general/woocommerce_default_customer_address`,
					{
						value: 'geolocation_ajax',
					}
				);
				await restApi.put(
					`${ WC_API_PATH }/settings/products/woocommerce_enable_ajax_add_to_cart`,
					{
						value: 'no',
					}
				);
				await addAProductToCart( page, productId );
				await page.goto( 'cart/' );

				await checkCartContentInBlocksCart(
					page,
					[
						{
							data: { name: productName, price: productPrice },
							qty: 1,
						},
					],
					parseFloat( productPrice )
				);

				// Reset settings.
				await restApi.put(
					`${ WC_API_PATH }/settings/general/woocommerce_default_customer_address`,
					{
						value: 'base',
					}
				);
				await restApi.put(
					`${ WC_API_PATH }/settings/products/woocommerce_enable_ajax_add_to_cart`,
					{
						value: 'yes',
					}
				);
			}
		);

		test(
			'should be able to navigate and remove item from mini cart using keyboard',
			{ tag: [ tags.COULD_BE_LOWER_LEVEL_TEST ] },
			async ( { page } ) => {
				await test.step( 'Add product to cart and open mini cart', async () => {
					await addAProductToCart( page, productId );
					const miniCartButton = page.locator(
						'.wc-block-mini-cart__button'
					);
					await miniCartButton.click();
					await expect(
						page.locator( '.wc-block-mini-cart__drawer' )
					).toBeVisible();
				} );

				await test.step( 'Verify and interact with remove button', async () => {
					const removeButton = page.locator(
						'.wc-block-cart-item__remove-link'
					);
					await expect( removeButton ).toBeVisible();
					await removeButton.focus();
					await page.keyboard.press( 'Space' );
				} );

				await test.step( 'Verify cart is empty', async () => {
					await expect(
						page.locator(
							'.wc-block-mini-cart__empty-cart-wrapper'
						)
					).toBeVisible();
					await expect(
						page.locator(
							'.wc-block-mini-cart__empty-cart-wrapper'
						)
					).toContainText( 'Your cart is empty' );
				} );
			}
		);
	}
);
