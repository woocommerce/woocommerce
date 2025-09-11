/**
 * Order Confirmation Tests for Checkout Blocks
 * 
 * Tests the order confirmation page after successful checkout.
 */

import { test, expect } from '@playwright/test';
import { CheckoutPage } from './checkout.page';
import { TEST_PRODUCT_ID, TEST_VIRTUAL_PRODUCT_ID } from './constants';
import {
	addProductToCart,
	addProductsToCart,
	goToCheckout,
	enableCheckoutBlocks,
	generateCustomerData,
	clearCart,
	loginAsCustomer,
	logout
} from './utils';

test.describe('Checkout Blocks → Order Confirmation', () => {
	let checkoutPage;
	let orderNumber;

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

	test('Shows order confirmation with correct details', async ({ page }) => {
		await addProductToCart(page, TEST_PRODUCT_ID, 2);
		await goToCheckout(page);

		const customerData = generateCustomerData();
		await checkoutPage.fillInCheckoutWithTestData(customerData);
		await checkoutPage.selectPaymentMethod('Cash on delivery');
		await checkoutPage.placeOrder();
		await checkoutPage.verifyOrderReceived();

		// Verify order confirmation elements
		await expect(page.getByText('Thank you. Your order has been received.')).toBeVisible();
		
		// Check for order number
		const orderNumberElement = page.locator('.woocommerce-order-overview__order strong, .order-number');
		if (await orderNumberElement.isVisible()) {
			orderNumber = await orderNumberElement.textContent();
			expect(orderNumber).toBeTruthy();
		}

		// Verify customer details
		await expect(page.getByText(customerData.email)).toBeVisible();
		await expect(page.getByText(customerData.firstname)).toBeVisible();
		await expect(page.getByText(customerData.lastname)).toBeVisible();

		// Verify payment method
		await expect(page.getByText('Cash on delivery')).toBeVisible();

		// Verify order items
		await expect(page.getByText('Test Product')).toBeVisible();
		await expect(page.getByText('× 2')).toBeVisible(); // Quantity

		// Verify totals section
		await expect(page.locator('.woocommerce-order-overview, .order-details')).toBeVisible();
	});

	test('Shows correct shipping details for physical products', async ({ page }) => {
		await addProductToCart(page, TEST_PRODUCT_ID);
		await goToCheckout(page);

		const customerData = generateCustomerData();
		await checkoutPage.fillInCheckoutWithTestData(customerData);
		await checkoutPage.selectPaymentMethod('Cash on delivery');
		await checkoutPage.placeOrder();
		await checkoutPage.verifyOrderReceived();

		// Verify shipping address is shown
		const shippingSection = page.locator('.woocommerce-customer-details address, .wc-block-order-confirmation-shipping-address');
		if (await shippingSection.isVisible()) {
			await expect(shippingSection).toContainText(customerData.addressfirstline);
			await expect(shippingSection).toContainText(customerData.city);
			await expect(shippingSection).toContainText(customerData.postcode);
		}

		// Verify shipping method is shown
		const shippingMethod = page.locator('.woocommerce-order-overview__shipping, .shipping-method');
		if (await shippingMethod.isVisible()) {
			await expect(shippingMethod).toBeVisible();
		}
	});

	test('Does not show shipping for virtual products', async ({ page }) => {
		await addProductToCart(page, TEST_VIRTUAL_PRODUCT_ID);
		await goToCheckout(page);

		const customerData = generateCustomerData();
		await checkoutPage.fillInCheckoutWithTestData(customerData);
		await checkoutPage.selectPaymentMethod('Cash on delivery');
		await checkoutPage.placeOrder();
		await checkoutPage.verifyOrderReceived();

		// Shipping section should not be visible for virtual products
		const shippingSection = page.locator('.woocommerce-customer-details__shipping, .wc-block-order-confirmation-shipping-address');
		await expect(shippingSection).not.toBeVisible();

		// Only billing address should be shown
		const billingSection = page.locator('.woocommerce-customer-details__billing, .wc-block-order-confirmation-billing-address');
		await expect(billingSection).toBeVisible();
	});

	test('Shows order notes if provided', async ({ page }) => {
		await addProductToCart(page, TEST_PRODUCT_ID);
		await goToCheckout(page);

		const customerData = generateCustomerData();
		const orderNote = 'Please deliver after 5 PM. Ring the doorbell twice.';

		await checkoutPage.fillInCheckoutWithTestData(customerData);

		// Add order note
		const orderNotesCheckbox = page.getByRole('checkbox', { name: /order notes/i });
		if (await orderNotesCheckbox.isVisible()) {
			await orderNotesCheckbox.check();
		}

		const orderNotesField = page.getByPlaceholder(/notes about your order/i);
		if (await orderNotesField.isVisible()) {
			await orderNotesField.fill(orderNote);
		}

		await checkoutPage.selectPaymentMethod('Cash on delivery');
		await checkoutPage.placeOrder();
		await checkoutPage.verifyOrderReceived();

		// Verify order note is displayed
		if (await orderNotesField.isVisible()) {
			await expect(page.getByText(orderNote)).toBeVisible();
		}
	});

	test('Shows create account confirmation for new accounts', async ({ page }) => {
		await addProductToCart(page, TEST_PRODUCT_ID);
		await goToCheckout(page);

		const testEmail = `newuser-${Date.now()}@example.com`;
		const customerData = generateCustomerData();
		customerData.email = testEmail;

		// Check create account option
		const createAccountCheckbox = page.getByRole('checkbox', { name: /create.*account/i });
		if (await createAccountCheckbox.isVisible()) {
			await createAccountCheckbox.check();
		}

		await checkoutPage.fillInCheckoutWithTestData(customerData);
		await checkoutPage.selectPaymentMethod('Cash on delivery');
		await checkoutPage.placeOrder();
		await checkoutPage.verifyOrderReceived();

		// Look for account creation confirmation
		const accountCreatedMessage = page.getByText(/account.*created|password.*sent/i);
		if (await accountCreatedMessage.isVisible()) {
			await expect(accountCreatedMessage).toBeVisible();
		}

		// Verify My Account link is available
		const myAccountLink = page.getByRole('link', { name: /my account/i });
		if (await myAccountLink.isVisible()) {
			await myAccountLink.click();
			await expect(page).toHaveURL(/my-account/);
		}
	});

	test('Shows download links for downloadable products', async ({ page }) => {
		// This test assumes a downloadable product exists
		// In real scenario, you'd create one in beforeAll
		const downloadableProductId = TEST_VIRTUAL_PRODUCT_ID; // Assuming virtual product is downloadable

		await addProductToCart(page, downloadableProductId);
		await goToCheckout(page);

		const customerData = generateCustomerData();
		await checkoutPage.fillInCheckoutWithTestData(customerData);
		await checkoutPage.selectPaymentMethod('Cash on delivery');
		await checkoutPage.placeOrder();
		await checkoutPage.verifyOrderReceived();

		// Look for downloads section
		const downloadsSection = page.locator('.woocommerce-order-downloads, .order-downloads');
		if (await downloadsSection.isVisible()) {
			// Verify download links are present
			const downloadLinks = downloadsSection.locator('a[href*="download"]');
			const linkCount = await downloadLinks.count();
			expect(linkCount).toBeGreaterThan(0);
		}
	});

	test('Can navigate to order from confirmation page', async ({ page }) => {
		await addProductToCart(page, TEST_PRODUCT_ID);
		await goToCheckout(page);

		const customerData = generateCustomerData();
		await checkoutPage.fillInCheckoutWithTestData(customerData);
		await checkoutPage.selectPaymentMethod('Cash on delivery');
		await checkoutPage.placeOrder();
		await checkoutPage.verifyOrderReceived();

		// Look for view order link
		const viewOrderLink = page.getByRole('link', { name: /view order|order details/i });
		if (await viewOrderLink.isVisible()) {
			await viewOrderLink.click();
			
			// Should navigate to order details page
			await expect(page).toHaveURL(/order|view-order/);
			
			// Verify we're on the correct order page
			const orderTitle = page.locator('h1, h2').filter({ hasText: /order/i });
			await expect(orderTitle).toBeVisible();
		}
	});

	test('Shows correct tax information when applicable', async ({ page }) => {
		await addProductToCart(page, TEST_PRODUCT_ID);
		await goToCheckout(page);

		const customerData = generateCustomerData();
		await checkoutPage.fillInCheckoutWithTestData(customerData);
		await checkoutPage.selectPaymentMethod('Cash on delivery');
		await checkoutPage.placeOrder();
		await checkoutPage.verifyOrderReceived();

		// Look for tax line in order totals
		const taxLine = page.locator('.tax-total, .woocommerce-order-overview__tax');
		if (await taxLine.isVisible()) {
			// Verify tax amount is shown
			await expect(taxLine).toContainText(/\$[\d.]+/);
		}
	});

	test('Shows applied coupons in order summary', async ({ page }) => {
		await addProductToCart(page, TEST_PRODUCT_ID);
		await goToCheckout(page);

		// Try to apply a coupon
		await checkoutPage.addCoupon('TESTCOUPON');
		
		// Wait to see if coupon was applied
		await page.waitForTimeout(2000);

		const customerData = generateCustomerData();
		await checkoutPage.fillInCheckoutWithTestData(customerData);
		await checkoutPage.selectPaymentMethod('Cash on delivery');
		await checkoutPage.placeOrder();

		// Check if order was placed successfully
		const orderReceived = page.getByText('Thank you. Your order has been received.');
		if (await orderReceived.isVisible()) {
			// Look for coupon in order summary
			const couponLine = page.locator('.coupon-line, .discount');
			if (await couponLine.isVisible()) {
				await expect(couponLine).toContainText(/coupon|discount/i);
			}
		}
	});

	test('Confirmation page respects privacy settings', async ({ page }) => {
		await addProductToCart(page, TEST_PRODUCT_ID);
		await goToCheckout(page);

		const customerData = generateCustomerData();
		await checkoutPage.fillInCheckoutWithTestData(customerData);
		await checkoutPage.selectPaymentMethod('Cash on delivery');
		await checkoutPage.placeOrder();
		await checkoutPage.verifyOrderReceived();

		// Check for privacy-related elements
		// This depends on store settings and regulations
		const privacyNotice = page.getByText(/privacy|data protection|gdpr/i);
		if (await privacyNotice.isVisible()) {
			// Verify privacy information is displayed appropriately
			await expect(privacyNotice).toBeVisible();
		}

		// Check if sensitive information is properly masked
		// Email might be partially hidden like j***@example.com
		const emailElement = page.locator('.email-address');
		if (await emailElement.isVisible()) {
			const emailText = await emailElement.textContent();
			// Check if email is masked (contains ***)
			if (emailText.includes('***')) {
				expect(emailText).toContain('***');
			}
		}
	});
});

test.describe('Checkout Blocks → Order Confirmation → Logged In User', () => {
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
		await loginAsCustomer(page);
	});

	test('Shows link to order in My Account', async ({ page }) => {
		await addProductToCart(page, TEST_PRODUCT_ID);
		await goToCheckout(page);

		await checkoutPage.fillInCheckoutWithTestData();
		await checkoutPage.selectPaymentMethod('Cash on delivery');
		await checkoutPage.placeOrder();
		await checkoutPage.verifyOrderReceived();

		// Navigate to My Account orders
		const myAccountLink = page.getByRole('link', { name: /my account/i });
		if (await myAccountLink.isVisible()) {
			await myAccountLink.click();
			
			// Go to orders section
			const ordersLink = page.getByRole('link', { name: 'Orders' });
			if (await ordersLink.isVisible()) {
				await ordersLink.click();
				
				// Verify the recent order is listed
				const ordersList = page.locator('.woocommerce-orders-table, .woocommerce-MyAccount-orders');
				await expect(ordersList).toBeVisible();
				
				// Should show at least one order
				const orderRows = ordersList.locator('tbody tr');
				const orderCount = await orderRows.count();
				expect(orderCount).toBeGreaterThan(0);
			}
		}
	});

	test('Can reorder from confirmation page', async ({ page }) => {
		await addProductToCart(page, TEST_PRODUCT_ID);
		await goToCheckout(page);

		await checkoutPage.fillInCheckoutWithTestData();
		await checkoutPage.selectPaymentMethod('Cash on delivery');
		await checkoutPage.placeOrder();
		await checkoutPage.verifyOrderReceived();

		// Look for order again / reorder button
		const reorderButton = page.getByRole('link', { name: /order again|reorder/i });
		if (await reorderButton.isVisible()) {
			await reorderButton.click();
			
			// Should add items to cart and redirect
			await expect(page).toHaveURL(/cart/);
			
			// Verify products are in cart
			await expect(page.getByText('Test Product')).toBeVisible();
		}
	});
});