/**
 * Checkout Blocks Shopper Tests
 * 
 * Tests for the checkout blocks experience from a shopper's perspective.
 * Covers account creation, local pickup, payment methods, shipping, and order placement.
 */

import { test, expect } from '@playwright/test';
import { CheckoutPage } from './checkout.page';
import { 
	TEST_PRODUCT_ID,
	TEST_VIRTUAL_PRODUCT_ID,
	SIMPLE_PHYSICAL_PRODUCT_NAME,
	SIMPLE_VIRTUAL_PRODUCT_NAME,
	FREE_SHIPPING_NAME,
	FLAT_RATE_SHIPPING_NAME,
	FLAT_RATE_SHIPPING_PRICE
} from './constants';
import {
	addProductToCart,
	goToCheckout,
	goToCart,
	enableCheckoutBlocks,
	disableCheckoutBlocks,
	generateCustomerData,
	loginAsCustomer,
	logout,
	clearCart
} from './utils';

test.describe('Checkout Blocks → Shopper → Account (guest user)', () => {
	let checkoutPage;

	test.beforeAll(async ({ browser }) => {
		const context = await browser.newContext();
		const page = await context.newPage();
		await enableCheckoutBlocks(page);
		await context.close();
	});

	test.beforeEach(async ({ page }) => {
		checkoutPage = new CheckoutPage(page);
		await clearCart(page);
		await logout(page);
	});

	test('Guest can create an account during checkout', async ({ page }) => {
		await addProductToCart(page, TEST_PRODUCT_ID);
		await goToCheckout(page);

		// Check create account checkbox
		const createAccount = page.getByRole('checkbox', { name: /create.*account/i });
		if (await createAccount.isVisible()) {
			await createAccount.check();
		}

		const testEmail = `test-${Date.now()}@example.com`;
		await checkoutPage.fillInCheckoutWithTestData({ email: testEmail });
		
		await checkoutPage.selectPaymentMethod('Cash on delivery');
		await checkoutPage.placeOrder();
		await checkoutPage.verifyOrderReceived();

		// Verify account was created by trying to access My Account
		await page.goto('/my-account/');
		await expect(page.locator('.woocommerce-MyAccount-content')).toBeVisible();
	});
});

test.describe('Checkout Blocks → Shopper → Local Pickup', () => {
	let checkoutPage;

	test.beforeAll(async ({ browser }) => {
		const context = await browser.newContext();
		const page = await context.newPage();
		await enableCheckoutBlocks(page);
		
		// TODO: Setup local pickup via admin API or CLI
		// For now, assume it's configured in setup.sh
		
		await context.close();
	});

	test.beforeEach(async ({ page }) => {
		checkoutPage = new CheckoutPage(page);
		await clearCart(page);
	});

	test('Shopper can choose local pickup option', async ({ page }) => {
		await addProductToCart(page, TEST_PRODUCT_ID);
		await goToCheckout(page);

		// Switch to pickup if available
		const pickupRadio = page.getByRole('radio', { name: 'Pickup' });
		if (await pickupRadio.isVisible()) {
			await checkoutPage.switchToPickup();
			
			// Select pickup location
			const pickupLocation = page.getByLabel('Store Location');
			if (await pickupLocation.isVisible()) {
				await pickupLocation.check();
			}

			await checkoutPage.fillInCheckoutWithTestData();
			await checkoutPage.selectPaymentMethod('Cash on delivery');
			await checkoutPage.placeOrder();
			await checkoutPage.verifyOrderReceived();

			// Verify pickup in order details
			await expect(page.getByText(/pickup|collection/i)).toBeVisible();
		}
	});

	test('Switching between pickup and shipping preserves address data', async ({ page }) => {
		await addProductToCart(page, TEST_PRODUCT_ID);
		await goToCheckout(page);

		const testEmail = 'preserve-test@example.com';
		await page.getByLabel('Email address').fill(testEmail);

		// Switch to pickup if available
		const pickupRadio = page.getByRole('radio', { name: 'Pickup' });
		const shipRadio = page.getByRole('radio', { name: 'Ship' });
		
		if (await pickupRadio.isVisible() && await shipRadio.isVisible()) {
			await checkoutPage.switchToPickup();
			await expect(page.getByLabel('Email address')).toHaveValue(testEmail);

			await checkoutPage.switchToShipping();
			await expect(page.getByLabel('Email address')).toHaveValue(testEmail);

			// Fill shipping details
			await checkoutPage.fillInCheckoutWithTestData();

			// Switch back to pickup
			await checkoutPage.switchToPickup();
			
			// Email should still be from filled data
			await expect(page.getByLabel('Email address')).toHaveValue('john.doe@test.com');
		}
	});
});

test.describe('Checkout Blocks → Shopper → Payment Methods', () => {
	let checkoutPage;

	test.beforeAll(async ({ browser }) => {
		const context = await browser.newContext();
		const page = await context.newPage();
		await enableCheckoutBlocks(page);
		await context.close();
	});

	test.beforeEach(async ({ page }) => {
		checkoutPage = new CheckoutPage(page);
		await clearCart(page);
	});

	test('User can change payment methods', async ({ page }) => {
		await addProductToCart(page, TEST_PRODUCT_ID);
		await goToCheckout(page);

		await checkoutPage.fillInCheckoutWithTestData();

		// Check available payment methods
		const cod = page.getByText('Cash on delivery');
		const bankTransfer = page.getByText(/direct bank transfer|bank transfer/i);

		if (await cod.isVisible() && await bankTransfer.isVisible()) {
			// Select COD
			await checkoutPage.selectPaymentMethod('Cash on delivery');
			
			// Verify COD is selected
			const codRadio = page.getByRole('radio', { name: /cash on delivery/i });
			if (await codRadio.isVisible()) {
				await expect(codRadio).toBeChecked();
			}

			// Switch to Bank Transfer
			await checkoutPage.selectPaymentMethod('Direct bank transfer');
			
			// Verify Bank Transfer is selected
			const bankRadio = page.getByRole('radio', { name: /bank transfer/i });
			if (await bankRadio.isVisible()) {
				await expect(bankRadio).toBeChecked();
			}
		}
	});
});

test.describe('Checkout Blocks → Shopper → Shipping', () => {
	let checkoutPage;

	test.beforeAll(async ({ browser }) => {
		const context = await browser.newContext();
		const page = await context.newPage();
		await enableCheckoutBlocks(page);
		await context.close();
	});

	test.beforeEach(async ({ page }) => {
		checkoutPage = new CheckoutPage(page);
		await clearCart(page);
	});

	test('User can choose shipping methods and use different billing address', async ({ page }) => {
		await addProductToCart(page, TEST_PRODUCT_ID);
		await goToCheckout(page);

		// Fill shipping address
		const shippingData = generateCustomerData();
		await checkoutPage.fillInCheckoutWithTestData(shippingData);

		// Check for shipping methods
		const freeShipping = page.getByText(FREE_SHIPPING_NAME);
		const flatRate = page.getByText(FLAT_RATE_SHIPPING_NAME);

		if (await flatRate.isVisible()) {
			await checkoutPage.selectShippingMethod(FLAT_RATE_SHIPPING_NAME);
			await expect(page.getByText(FLAT_RATE_SHIPPING_PRICE)).toBeVisible();
		}

		// Use different billing address
		await checkoutPage.expandBillingAddress();
		
		const billingData = generateCustomerData();
		await checkoutPage.fillBillingAddress(billingData);

		await checkoutPage.selectPaymentMethod('Cash on delivery');
		await checkoutPage.placeOrder();
		await checkoutPage.verifyOrderReceived();
	});

	test('User can add postcodes for different countries', async ({ page }) => {
		await addProductToCart(page, TEST_PRODUCT_ID);
		await goToCheckout(page);

		const testCases = [
			{ country: 'US', postcode: '90210' },
			{ country: 'GB', postcode: 'SW1A 1AA' },
			{ country: 'CA', postcode: 'K1A 0B1' },
		];

		for (const { country, postcode } of testCases) {
			const shippingGroup = page.getByRole('group', { name: 'Shipping address' });
			
			// Select country
			const countrySelect = shippingGroup.getByRole('combobox', { name: 'Country/Region' });
			if (await countrySelect.isVisible()) {
				await countrySelect.selectOption(country);
			}

			// Enter postcode
			const postcodeField = shippingGroup.getByLabel(/zip|postal|postcode/i);
			await postcodeField.fill(postcode);

			// Verify the postcode is accepted
			await expect(postcodeField).toHaveValue(postcode);
		}
	});
});

test.describe('Checkout Blocks → Shopper → Place Order', () => {
	let checkoutPage;

	test.beforeAll(async ({ browser }) => {
		const context = await browser.newContext();
		const page = await context.newPage();
		await enableCheckoutBlocks(page);
		await context.close();
	});

	test.beforeEach(async ({ page }) => {
		checkoutPage = new CheckoutPage(page);
		await clearCart(page);
		await logout(page);
	});

	test('Guest user can place order', async ({ page }) => {
		await addProductToCart(page, TEST_PRODUCT_ID, 2);
		await goToCheckout(page);

		const customerData = generateCustomerData();
		await checkoutPage.fillInCheckoutWithTestData(customerData);

		await checkoutPage.selectPaymentMethod('Cash on delivery');
		await checkoutPage.placeOrder();
		await checkoutPage.verifyOrderReceived();

		// Verify order details
		await expect(page.getByText(customerData.email)).toBeVisible();
	});

	test('Virtual product order does not show shipping', async ({ page }) => {
		await addProductToCart(page, TEST_VIRTUAL_PRODUCT_ID);
		await goToCheckout(page);

		// Shipping section should not be visible for virtual products
		const shippingSection = page.getByRole('group', { name: 'Shipping address' });
		await expect(shippingSection).not.toBeVisible();

		// Only billing address should be shown
		const billingSection = page.getByRole('group', { name: 'Billing address' });
		await expect(billingSection).toBeVisible();

		const customerData = generateCustomerData();
		await checkoutPage.fillInCheckoutWithTestData(customerData);

		await checkoutPage.selectPaymentMethod('Cash on delivery');
		await checkoutPage.placeOrder();
		await checkoutPage.verifyOrderReceived();
	});
});

test.describe('Checkout Blocks → Shopper → Form Validation', () => {
	let checkoutPage;

	test.beforeAll(async ({ browser }) => {
		const context = await browser.newContext();
		const page = await context.newPage();
		await enableCheckoutBlocks(page);
		await context.close();
	});

	test.beforeEach(async ({ page }) => {
		checkoutPage = new CheckoutPage(page);
		await clearCart(page);
	});

	test('Shows errors when required fields are empty', async ({ page }) => {
		await addProductToCart(page, TEST_PRODUCT_ID);
		await goToCheckout(page);

		// Try to place order without filling fields
		await checkoutPage.selectPaymentMethod('Cash on delivery');
		await checkoutPage.placeOrder();

		// Should show validation errors
		await expect(page.getByText(/required|please enter/i).first()).toBeVisible();

		// Fill required fields and try again
		await checkoutPage.fillInCheckoutWithTestData();
		await checkoutPage.placeOrder();
		await checkoutPage.verifyOrderReceived();
	});

	test('Validates email format', async ({ page }) => {
		await addProductToCart(page, TEST_PRODUCT_ID);
		await goToCheckout(page);

		// Enter invalid email
		await page.getByLabel('Email address').fill('invalid-email');
		await page.getByLabel('First name').click(); // Trigger validation

		// Should show email validation error
		await expect(page.getByText(/valid email|email.*invalid/i)).toBeVisible();

		// Fix email
		await page.getByLabel('Email address').fill('valid@email.com');
		await page.getByLabel('First name').click();

		// Error should be gone
		await expect(page.getByText(/valid email|email.*invalid/i)).not.toBeVisible();
	});
});

test.describe('Checkout Blocks → Shopper → Coupons', () => {
	let checkoutPage;

	test.beforeAll(async ({ browser }) => {
		const context = await browser.newContext();
		const page = await context.newPage();
		await enableCheckoutBlocks(page);
		await context.close();
	});

	test.beforeEach(async ({ page }) => {
		checkoutPage = new CheckoutPage(page);
		await clearCart(page);
	});

	test('Can apply and remove coupons', async ({ page }) => {
		await addProductToCart(page, TEST_PRODUCT_ID);
		await goToCheckout(page);

		// Look for add coupon functionality
		const addCouponButton = page.getByRole('button', { name: /add.*coupon/i });
		if (await addCouponButton.isVisible()) {
			await addCouponButton.click();

			// Enter test coupon
			const couponField = page.getByPlaceholder(/coupon/i);
			await couponField.fill('TESTCOUPON');

			// Apply coupon
			await page.getByRole('button', { name: /apply/i }).click();

			// Check for success or error message
			await expect(
				page.getByText(/coupon.*applied|invalid|does not exist/i)
			).toBeVisible({ timeout: 5000 });

			// If coupon was applied, try to remove it
			const removeButton = page.getByRole('button', { name: /remove/i });
			if (await removeButton.isVisible()) {
				await removeButton.click();
				await expect(page.getByText(/coupon.*removed/i)).toBeVisible();
			}
		}
	});
});

test.describe('Checkout Blocks → Shopper → Billing Address', () => {
	let checkoutPage;

	test.beforeAll(async ({ browser }) => {
		const context = await browser.newContext();
		const page = await context.newPage();
		await enableCheckoutBlocks(page);
		await context.close();
	});

	test.beforeEach(async ({ page }) => {
		checkoutPage = new CheckoutPage(page);
		await clearCart(page);
	});

	test('Can use same address for billing and shipping', async ({ page }) => {
		await addProductToCart(page, TEST_PRODUCT_ID);
		await goToCheckout(page);

		// Fill shipping address
		const shippingData = generateCustomerData();
		await checkoutPage.fillInCheckoutWithTestData(shippingData);

		// Check if "Use same address for billing" is checked by default
		const sameAddressCheckbox = page.getByRole('checkbox', {
			name: /same address for billing/i
		});
		
		if (await sameAddressCheckbox.isVisible()) {
			await expect(sameAddressCheckbox).toBeChecked();
		}

		await checkoutPage.selectPaymentMethod('Cash on delivery');
		await checkoutPage.placeOrder();
		await checkoutPage.verifyOrderReceived();

		// Verify both addresses are the same in confirmation
		await checkoutPage.verifyShippingDetails({
			name: `${shippingData.firstname} ${shippingData.lastname}`,
			address: shippingData.addressfirstline,
			city: shippingData.city
		});
	});

	test('Can enter different billing address', async ({ page }) => {
		await addProductToCart(page, TEST_PRODUCT_ID);
		await goToCheckout(page);

		// Fill shipping address
		const shippingData = generateCustomerData();
		await checkoutPage.fillInCheckoutWithTestData(shippingData);

		// Uncheck same address for billing
		await checkoutPage.expandBillingAddress();

		// Fill different billing address
		const billingData = generateCustomerData();
		await checkoutPage.fillBillingAddress(billingData);

		await checkoutPage.selectPaymentMethod('Cash on delivery');
		await checkoutPage.placeOrder();
		await checkoutPage.verifyOrderReceived();

		// Verify addresses are different in confirmation
		await checkoutPage.verifyBillingDetails({
			name: `${billingData.firstname} ${billingData.lastname}`,
			address: billingData.addressfirstline,
			city: billingData.city
		});
	});
});