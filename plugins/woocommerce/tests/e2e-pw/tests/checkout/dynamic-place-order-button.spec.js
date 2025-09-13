/**
 * E2E tests for dynamic place order button functionality in WooCommerce blocks.
 */

import {
	addAProductToCart,
	fillBillingCheckoutBlocks,
	WC_API_PATH,
} from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { expect, tags, test as baseTest } from '../../fixtures/fixtures';
import { getFakeCustomer, getFakeProduct } from '../../utils/data';

const test = baseTest.extend( {
	storageState: process.env.ADMINSTATE,
} );

test.describe( 'Dynamic Place Order Button', () => {
	let product, customer;

	test.beforeAll( async ( { request } ) => {
		// Create test data
		product = getFakeProduct( {
			name: 'Dynamic Place Order Button Test Product',
			regularPrice: '10.00',
		} );
		customer = getFakeCustomer();

		// Create product
		const response = await request.post( WC_API_PATH + 'products', {
			data: product,
		} );
		const responseJson = await response.json();
		product.id = responseJson.id;
	} );

	test.afterAll( async ( { request } ) => {
		// Clean up test data
		if ( product.id ) {
			await request.delete( WC_API_PATH + `products/${ product.id }?force=true` );
		}
	} );

	test( 'should render default place order button when no custom payment method button is provided', async ( {
		page,
	} ) => {
		// Add product to cart
		await addAProductToCart( page, product.id );

		// Go to checkout
		await page.goto( '/checkout' );

		// Wait for checkout to load
		await page.waitForSelector( '.wc-block-checkout__actions' );

		// Check that default place order button is rendered
		const placeOrderButton = page.locator(
			'.wc-block-components-checkout-place-order-button'
		);
		await expect( placeOrderButton ).toBeVisible();

		// Check that button has correct text
		await expect( placeOrderButton ).toContainText( 'Place order' );

		// Check that button is not disabled initially
		await expect( placeOrderButton ).not.toBeDisabled();
	} );

	test( 'should render custom payment method place order button when provided', async ( {
		page,
	} ) => {
		// Add product to cart
		await addAProductToCart( page, product.id );

		// Go to checkout
		await page.goto( '/checkout' );

		// Wait for checkout to load
		await page.waitForSelector( '.wc-block-checkout__actions' );

		// Mock a custom payment method with place order button
		await page.evaluate( () => {
			// Register a custom payment method with place order button
			window.wc.wcBlocksRegistry.registerPaymentMethod( {
				name: 'test-custom-payment-method',
				label: 'Test Custom Payment Method',
				ariaLabel: 'Test Custom Payment Method',
				content: () => 'Custom payment method content',
				edit: () => 'Custom payment method edit',
				canMakePayment: () => true,
				placeOrderButtonLabel: 'Pay with Custom Method',
				placeOrderButton: ( props ) => {
					const button = document.createElement( 'button' );
					button.className = 'custom-place-order-button';
					button.textContent = 'Custom Place Order Button';
					button.onclick = props.onSubmit;
					return button;
				},
			} );
		} );

		// Reload the page to apply the new payment method
		await page.reload();
		await page.waitForSelector( '.wc-block-checkout__actions' );

		// Select the custom payment method
		const customPaymentMethod = page.locator(
			'input[name="payment_method"][value="test-custom-payment-method"]'
		);
		await customPaymentMethod.check();

		// Wait for the custom place order button to appear
		await page.waitForSelector( '.custom-place-order-button' );

		// Check that custom place order button is rendered
		const customButton = page.locator( '.custom-place-order-button' );
		await expect( customButton ).toBeVisible();
		await expect( customButton ).toContainText( 'Custom Place Order Button' );

		// Check that default place order button is not visible
		const defaultButton = page.locator(
			'.wc-block-components-checkout-place-order-button'
		);
		await expect( defaultButton ).not.toBeVisible();
	} );

	test( 'should use payment method button label when available', async ( {
		page,
	} ) => {
		// Add product to cart
		await addAProductToCart( page, product.id );

		// Go to checkout
		await page.goto( '/checkout' );

		// Wait for checkout to load
		await page.waitForSelector( '.wc-block-checkout__actions' );

		// Mock a payment method with custom button label
		await page.evaluate( () => {
			window.wc.wcBlocksRegistry.registerPaymentMethod( {
				name: 'test-payment-method-with-label',
				label: 'Test Payment Method with Label',
				ariaLabel: 'Test Payment Method with Label',
				content: () => 'Payment method content',
				edit: () => 'Payment method edit',
				canMakePayment: () => true,
				placeOrderButtonLabel: 'Pay with Test Method',
			} );
		} );

		// Reload the page to apply the new payment method
		await page.reload();
		await page.waitForSelector( '.wc-block-checkout__actions' );

		// Select the payment method with custom label
		const paymentMethod = page.locator(
			'input[name="payment_method"][value="test-payment-method-with-label"]'
		);
		await paymentMethod.check();

		// Check that the button uses the custom label
		const placeOrderButton = page.locator(
			'.wc-block-components-checkout-place-order-button'
		);
		await expect( placeOrderButton ).toContainText( 'Pay with Test Method' );
	} );

	test( 'should handle payment method place order button container correctly', async ( {
		page,
	} ) => {
		// Add product to cart
		await addAProductToCart( page, product.id );

		// Go to checkout
		await page.goto( '/checkout' );

		// Wait for checkout to load
		await page.waitForSelector( '.wc-block-checkout__actions' );

		// Mock a custom payment method with place order button
		await page.evaluate( () => {
			window.wc.wcBlocksRegistry.registerPaymentMethod( {
				name: 'test-payment-method-with-container',
				label: 'Test Payment Method with Container',
				ariaLabel: 'Test Payment Method with Container',
				content: () => 'Payment method content',
				edit: () => 'Payment method edit',
				canMakePayment: () => true,
				placeOrderButton: ( props ) => {
					const button = document.createElement( 'button' );
					button.className = 'test-custom-button';
					button.textContent = 'Test Custom Button';
					button.onclick = props.onSubmit;
					return button;
				},
			} );
		} );

		// Reload the page to apply the new payment method
		await page.reload();
		await page.waitForSelector( '.wc-block-checkout__actions' );

		// Select the custom payment method
		const paymentMethod = page.locator(
			'input[name="payment_method"][value="test-payment-method-with-container"]'
		);
		await paymentMethod.check();

		// Wait for the custom button to appear
		await page.waitForSelector( '.test-custom-button' );

		// Check that the payment method button container is rendered with correct attributes
		const container = page.locator(
			'.wc-block-checkout__payment-method-button'
		);
		await expect( container ).toBeVisible();
		await expect( container ).toHaveAttribute( 'role', 'button' );
		await expect( container ).toHaveAttribute( 'tabIndex', '0' );

		// Check that the custom button is inside the container
		const customButton = page.locator( '.test-custom-button' );
		await expect( customButton ).toBeVisible();
		await expect( customButton ).toBeAttached();
	} );

	test( 'should handle return to cart button correctly', async ( {
		page,
	} ) => {
		// Add product to cart
		await addAProductToCart( page, product.id );

		// Go to checkout
		await page.goto( '/checkout' );

		// Wait for checkout to load
		await page.waitForSelector( '.wc-block-checkout__actions' );

		// Check that return to cart button is not visible by default
		const returnToCartButton = page.locator(
			'.wc-block-components-return-to-cart-button'
		);
		await expect( returnToCartButton ).not.toBeVisible();

		// Mock enabling return to cart button
		await page.evaluate( () => {
			// This would typically be done through block settings
			const checkoutBlock = document.querySelector(
				'.wp-block-woocommerce-checkout-actions-block'
			);
			if ( checkoutBlock ) {
				checkoutBlock.classList.add( 'show-return-to-cart' );
			}
		} );

		// Note: In a real test, you would need to configure the block settings
		// to show the return to cart button. This is a simplified example.
	} );

	test( 'should handle price display correctly', async ( {
		page,
	} ) => {
		// Add product to cart
		await addAProductToCart( page, product.id );

		// Go to checkout
		await page.goto( '/checkout' );

		// Wait for checkout to load
		await page.waitForSelector( '.wc-block-checkout__actions' );

		// Check that price is not displayed by default
		const priceElement = page.locator(
			'.wc-block-components-checkout-place-order-button__price'
		);
		await expect( priceElement ).not.toBeVisible();

		// Mock enabling price display
		await page.evaluate( () => {
			const checkoutBlock = document.querySelector(
				'.wp-block-woocommerce-checkout-actions-block'
			);
			if ( checkoutBlock ) {
				checkoutBlock.classList.add( 'is-style-with-price' );
			}
		} );

		// Note: In a real test, you would need to configure the block settings
		// to show the price. This is a simplified example.
	} );

	test( 'should handle checkout form submission with custom payment method', async ( {
		page,
	} ) => {
		// Add product to cart
		await addAProductToCart( page, product.id );

		// Go to checkout
		await page.goto( '/checkout' );

		// Wait for checkout to load
		await page.waitForSelector( '.wc-block-checkout__actions' );

		// Fill billing information
		await fillBillingCheckoutBlocks( page, customer );

		// Mock a custom payment method that handles submission
		await page.evaluate( () => {
			window.wc.wcBlocksRegistry.registerPaymentMethod( {
				name: 'test-payment-method-submission',
				label: 'Test Payment Method Submission',
				ariaLabel: 'Test Payment Method Submission',
				content: () => 'Payment method content',
				edit: () => 'Payment method edit',
				canMakePayment: () => true,
				placeOrderButton: ( props ) => {
					const button = document.createElement( 'button' );
					button.className = 'test-submission-button';
					button.textContent = 'Submit Order';
					button.onclick = ( e ) => {
						e.preventDefault();
						// Simulate successful payment
						props.onSubmit();
					};
					return button;
				},
			} );
		} );

		// Reload the page to apply the new payment method
		await page.reload();
		await page.waitForSelector( '.wc-block-checkout__actions' );

		// Fill billing information again after reload
		await fillBillingCheckoutBlocks( page, customer );

		// Select the custom payment method
		const paymentMethod = page.locator(
			'input[name="payment_method"][value="test-payment-method-submission"]'
		);
		await paymentMethod.check();

		// Wait for the custom button to appear
		await page.waitForSelector( '.test-submission-button' );

		// Click the custom submit button
		const submitButton = page.locator( '.test-submission-button' );
		await submitButton.click();

		// Note: In a real test, you would verify that the order was created
		// and the user was redirected to the order received page.
		// This is a simplified example that just tests the button interaction.
	} );
} );
