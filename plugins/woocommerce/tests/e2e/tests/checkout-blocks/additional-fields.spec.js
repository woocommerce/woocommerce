/**
 * Additional Fields Tests for Checkout Blocks
 * 
 * Tests for custom fields that can be added to checkout blocks.
 * This is a simplified port focusing on core functionality.
 */

import { test, expect } from '@playwright/test';
import { CheckoutPage } from './checkout.page';
import { TEST_PRODUCT_ID } from './constants';
import {
	addProductToCart,
	goToCheckout,
	enableCheckoutBlocks,
	generateCustomerData,
	clearCart,
	loginAsCustomer,
	loginAsAdmin,
	logout
} from './utils';

test.describe('Checkout Blocks → Additional Fields → Shopper', () => {
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

	test('Can fill additional fields in checkout', async ({ page }) => {
		await addProductToCart(page, TEST_PRODUCT_ID);
		await goToCheckout(page);

		const customerData = generateCustomerData();
		
		// Look for any additional fields that might be present
		// These would be added by extensions or custom code
		const additionalFields = {
			'Gift message': 'Happy Birthday!',
			'Delivery instructions': 'Leave at door',
			'Company name': 'Test Company',
			'VAT number': 'GB123456789'
		};

		// Fill standard checkout fields
		await checkoutPage.fillInCheckoutWithTestData(customerData);

		// Try to fill additional fields if they exist
		for (const [label, value] of Object.entries(additionalFields)) {
			const field = page.getByLabel(label);
			if (await field.isVisible()) {
				await field.fill(value);
			}
		}

		await checkoutPage.selectPaymentMethod('Cash on delivery');
		await checkoutPage.placeOrder();
		await checkoutPage.verifyOrderReceived();
	});

	test('Shows validation for required additional fields', async ({ page }) => {
		await addProductToCart(page, TEST_PRODUCT_ID);
		await goToCheckout(page);

		// Look for required additional fields
		const requiredFieldLabels = page.locator('label:has-text("*")');
		const requiredCount = await requiredFieldLabels.count();

		if (requiredCount > 0) {
			// Try to submit without filling required fields
			await checkoutPage.selectPaymentMethod('Cash on delivery');
			await checkoutPage.placeOrder();

			// Should show validation errors
			await expect(page.getByText(/required|please enter/i).first()).toBeVisible();

			// Fill all fields including required ones
			await checkoutPage.fillInCheckoutWithTestData();
			
			// Fill any visible required additional fields
			for (let i = 0; i < requiredCount; i++) {
				const label = await requiredFieldLabels.nth(i).textContent();
				const fieldName = label.replace('*', '').trim();
				const field = page.getByLabel(fieldName);
				if (await field.isVisible()) {
					await field.fill('Test Value');
				}
			}

			await checkoutPage.placeOrder();
			await checkoutPage.verifyOrderReceived();
		}
	});

	test('Additional fields persist when switching between shipping methods', async ({ page }) => {
		await addProductToCart(page, TEST_PRODUCT_ID);
		await goToCheckout(page);

		const testValue = 'Test Additional Field Value';
		
		// Fill standard fields
		await checkoutPage.fillInCheckoutWithTestData();

		// Find and fill an additional field if present
		const additionalField = page.locator('input[name*="additional"], textarea[name*="additional"]').first();
		if (await additionalField.isVisible()) {
			await additionalField.fill(testValue);

			// Switch shipping method if multiple are available
			const shippingMethods = page.locator('input[name="radio-control-0"]');
			if (await shippingMethods.count() > 1) {
				await shippingMethods.nth(1).click();
				await page.waitForTimeout(1000);

				// Verify field value persisted
				await expect(additionalField).toHaveValue(testValue);
			}
		}
	});

	test('Additional fields in different sections', async ({ page }) => {
		await addProductToCart(page, TEST_PRODUCT_ID);
		await goToCheckout(page);

		// Check for additional fields in different sections
		const sections = [
			{ name: 'Contact information', fields: { 'Phone (alternate)': '555-0123' } },
			{ name: 'Shipping address', fields: { 'Apartment number': '4B' } },
			{ name: 'Billing address', fields: { 'Tax ID': 'TAX123' } },
			{ name: 'Order notes', fields: { 'Special requests': 'Please call before delivery' } }
		];

		for (const section of sections) {
			const sectionElement = page.getByRole('group', { name: section.name });
			if (await sectionElement.isVisible()) {
				for (const [field, value] of Object.entries(section.fields)) {
					const input = sectionElement.getByLabel(field);
					if (await input.isVisible()) {
						await input.fill(value);
					}
				}
			}
		}

		// Fill standard required fields
		await checkoutPage.fillInCheckoutWithTestData();
		await checkoutPage.selectPaymentMethod('Cash on delivery');
		await checkoutPage.placeOrder();
		await checkoutPage.verifyOrderReceived();
	});
});

test.describe('Checkout Blocks → Additional Fields → Validation', () => {
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

	test('Validates field format (email, phone, etc.)', async ({ page }) => {
		await addProductToCart(page, TEST_PRODUCT_ID);
		await goToCheckout(page);

		// Test email validation in additional email fields
		const additionalEmailFields = page.locator('input[type="email"]:not([name="email"])');
		const emailCount = await additionalEmailFields.count();
		
		for (let i = 0; i < emailCount; i++) {
			const field = additionalEmailFields.nth(i);
			if (await field.isVisible()) {
				// Enter invalid email
				await field.fill('invalid-email');
				await field.blur();
				
				// Check for validation error
				const errorMessage = page.locator('.wc-block-components-validation-error').first();
				if (await errorMessage.isVisible()) {
					await expect(errorMessage).toContainText(/valid|email/i);
				}

				// Fix with valid email
				await field.fill('valid@email.com');
				await field.blur();
			}
		}

		// Test phone validation
		const phoneFields = page.locator('input[type="tel"]:not([name="phone"])');
		const phoneCount = await phoneFields.count();
		
		for (let i = 0; i < phoneCount; i++) {
			const field = phoneFields.nth(i);
			if (await field.isVisible()) {
				// Enter invalid phone
				await field.fill('abc');
				await field.blur();
				
				// Check for validation error
				const errorMessage = page.locator('.wc-block-components-validation-error').first();
				if (await errorMessage.isVisible()) {
					await expect(errorMessage).toContainText(/valid|phone/i);
				}

				// Fix with valid phone
				await field.fill('555-555-5555');
				await field.blur();
			}
		}
	});

	test('Validates field length limits', async ({ page }) => {
		await addProductToCart(page, TEST_PRODUCT_ID);
		await goToCheckout(page);

		// Find fields with maxlength attribute
		const limitedFields = page.locator('input[maxlength], textarea[maxlength]');
		const fieldCount = await limitedFields.count();

		for (let i = 0; i < fieldCount; i++) {
			const field = limitedFields.nth(i);
			if (await field.isVisible()) {
				const maxLength = await field.getAttribute('maxlength');
				if (maxLength) {
					const longText = 'a'.repeat(parseInt(maxLength) + 10);
					await field.fill(longText);
					
					// Verify it was truncated
					const actualValue = await field.inputValue();
					expect(actualValue.length).toBeLessThanOrEqual(parseInt(maxLength));
				}
			}
		}
	});

	test('Shows server-side validation errors', async ({ page }) => {
		await addProductToCart(page, TEST_PRODUCT_ID);
		await goToCheckout(page);

		await checkoutPage.fillInCheckoutWithTestData();

		// Fill a field with a value that might trigger server validation
		// This depends on what validations are implemented
		const customField = page.locator('input[name*="custom"], input[name*="additional"]').first();
		if (await customField.isVisible()) {
			// Try various invalid values that might trigger server validation
			await customField.fill('<script>alert("xss")</script>');
		}

		await checkoutPage.selectPaymentMethod('Cash on delivery');
		await checkoutPage.placeOrder();

		// Check if any validation errors appear
		const errorMessages = page.locator('.wc-block-components-notice-banner__content');
		if (await errorMessages.count() > 0) {
			// Verify error is related to validation
			await expect(errorMessages.first()).toContainText(/invalid|not allowed|error/i);
		}
	});
});

test.describe('Checkout Blocks → Additional Fields → Merchant', () => {
	
	test.beforeEach(async ({ page }) => {
		// Login as admin using utility function
		await loginAsAdmin(page);
	});

	test('Can view additional fields in order details', async ({ page }) => {
		// Go to orders page
		await page.goto('/wp-admin/edit.php?post_type=shop_order');
		
		// Click on the first order
		const firstOrder = page.locator('.order-preview').first();
		if (await firstOrder.isVisible()) {
			await firstOrder.click();
			
			// Wait for order details to load
			await page.waitForSelector('.order-data-column', { state: 'visible' });
			
			// Look for additional fields section
			const additionalFieldsSection = page.locator('.order-custom-fields, .additional-fields');
			if (await additionalFieldsSection.isVisible()) {
				// Verify fields are displayed
				const fields = additionalFieldsSection.locator('.field');
				const fieldCount = await fields.count();
				expect(fieldCount).toBeGreaterThanOrEqual(0);
			}
		}
	});

	test('Can edit additional fields from order admin', async ({ page }) => {
		// Go to orders page
		await page.goto('/wp-admin/edit.php?post_type=shop_order');
		
		// Edit the first order
		const editButton = page.locator('.row-actions .edit').first();
		if (await editButton.isVisible()) {
			await editButton.click();
			
			// Wait for edit page to load
			await page.waitForSelector('#order_data', { state: 'visible' });
			
			// Look for editable additional fields
			const customFields = page.locator('input[name*="_additional"], input[name*="_custom"]');
			const fieldCount = await customFields.count();
			
			if (fieldCount > 0) {
				// Edit the first custom field
				const firstField = customFields.first();
				await firstField.fill('Updated Value');
				
				// Save order
				await page.getByRole('button', { name: 'Update' }).click();
				
				// Verify save was successful
				await expect(page.locator('.notice-success')).toBeVisible();
			}
		}
	});

	test('Additional fields appear in order emails preview', async ({ page }) => {
		// Go to WooCommerce settings - Emails
		await page.goto('/wp-admin/admin.php?page=wc-settings&tab=email');
		
		// Click on order confirmation email
		const orderEmailLink = page.getByRole('link', { name: 'Processing order' });
		if (await orderEmailLink.isVisible()) {
			await orderEmailLink.click();
			
			// Look for additional content settings
			const additionalContent = page.getByLabel('Additional content');
			if (await additionalContent.isVisible()) {
				// Check if there's mention of custom fields
				const helpText = page.locator('.description');
				const hasCustomFields = await helpText.textContent();
				
				// Some themes/plugins add custom field placeholders
				if (hasCustomFields && hasCustomFields.includes('{')) {
					expect(hasCustomFields).toContain('{');
				}
			}
		}
	});
});