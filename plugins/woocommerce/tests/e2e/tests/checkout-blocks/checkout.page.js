/**
 * Page object for checkout blocks
 */

import { expect } from '@playwright/test';
import {
	FREE_SHIPPING_NAME,
	SIMPLE_PHYSICAL_PRODUCT_NAME,
	SIMPLE_VIRTUAL_PRODUCT_NAME,
} from './constants';

export class CheckoutPage {
	constructor(page, requestUtils = null) {
		this.page = page;
		this.requestUtils = requestUtils;
		this.testData = {
			firstname: 'John',
			lastname: 'Doe',
			addressfirstline: '123 Easy Street',
			addresssecondline: 'Testville',
			country: 'United States (US)',
			countryKey: 'US',
			city: 'Los Angeles',
			state: 'California',
			postcode: '90210',
			email: 'john.doe@test.com',
			phone: '01234567890',
		};
	}

	async isShippingRateSelected(shippingName, shippingPrice) {
		const shippingLine = this.page.locator(
			'.wc-block-components-totals-shipping'
		);

		const nameIsVisible = await shippingLine
			.getByText(shippingName)
			.isVisible();
		const priceIsVisible = await shippingLine
			.getByText(shippingPrice, { exact: true })
			.isVisible();
		return nameIsVisible && priceIsVisible;
	}

	async fillContactInformation(email, additionalFields = {}) {
		await this.page
			.getByLabel('Email address')
			.first()
			.fill(email);

		// Fill additional contact fields if provided
		for (const [key, value] of Object.entries(additionalFields)) {
			const field = this.page.getByLabel(key);
			if (await field.isVisible()) {
				await field.fill(value);
			}
		}
	}

	async fillShippingAddress(data, additionalFields = {}) {
		const group = this.page.getByRole('group', { name: 'Shipping address' });
		
		await group.getByLabel('First name').fill(data.firstname);
		await group.getByLabel('Last name').fill(data.lastname);
		await group.getByLabel('Address', { exact: true }).fill(data.addressfirstline);
		if (data.addresssecondline) {
			await group.getByLabel('Apartment, suite, etc.').fill(data.addresssecondline);
		}
		await group.getByLabel('City').fill(data.city);
		await group.getByLabel('ZIP Code').fill(data.postcode);
		
		// Handle country/state selection
		const countryInput = group.getByRole('combobox', { name: 'Country/Region' });
		if (await countryInput.isVisible()) {
			await countryInput.selectOption(data.countryKey || 'US');
		}
		
		const stateInput = group.getByRole('combobox', { name: 'State' });
		if (await stateInput.isVisible()) {
			await stateInput.selectOption(data.state);
		}

		// Fill additional shipping fields if provided
		for (const [key, value] of Object.entries(additionalFields)) {
			const field = group.getByLabel(key);
			if (await field.isVisible()) {
				await field.fill(value);
			}
		}
	}

	async fillBillingAddress(data, additionalFields = {}) {
		const group = this.page.getByRole('group', { name: 'Billing address' });
		
		await group.getByLabel('First name').fill(data.firstname);
		await group.getByLabel('Last name').fill(data.lastname);
		await group.getByLabel('Address', { exact: true }).fill(data.addressfirstline);
		if (data.addresssecondline) {
			await group.getByLabel('Apartment, suite, etc.').fill(data.addresssecondline);
		}
		await group.getByLabel('City').fill(data.city);
		await group.getByLabel('ZIP Code').fill(data.postcode);
		await group.getByLabel('Phone').fill(data.phone);
		
		// Handle country/state selection
		const countryInput = group.getByRole('combobox', { name: 'Country/Region' });
		if (await countryInput.isVisible()) {
			await countryInput.selectOption(data.countryKey || 'US');
		}
		
		const stateInput = group.getByRole('combobox', { name: 'State' });
		if (await stateInput.isVisible()) {
			await stateInput.selectOption(data.state);
		}

		// Fill additional billing fields if provided
		for (const [key, value] of Object.entries(additionalFields)) {
			const field = group.getByLabel(key);
			if (await field.isVisible()) {
				await field.fill(value);
			}
		}
	}

	async fillInCheckoutWithTestData(overrideData = {}, additionalFields = {
		address: { shipping: {}, billing: {} },
		order: {},
		contact: {},
	}) {
		await this.page
			.getByRole('group', { name: 'Shipping address' })
			.or(this.page.getByRole('group', { name: 'Billing address' }))
			.first()
			.waitFor({ state: 'visible' });

		const isShippingOpen = await this.page
			.getByRole('group', {
				name: 'Shipping address',
			})
			.isVisible();

		const isBillingOpen = await this.page
			.getByRole('group', {
				name: 'Billing address',
			})
			.isVisible();

		const testData = { ...this.testData, ...overrideData };

		await this.fillContactInformation(
			testData.email,
			additionalFields.contact || {}
		);

		if (isShippingOpen) {
			await this.fillShippingAddress(testData, additionalFields.address?.shipping || {});
		}

		if (isBillingOpen) {
			await this.fillBillingAddress(testData, additionalFields.address?.billing || {});
		}

		// Fill order notes or additional order fields
		for (const [key, value] of Object.entries(additionalFields.order || {})) {
			const field = this.page.getByLabel(key);
			if (await field.isVisible()) {
				await field.fill(value);
			}
		}
	}

	async selectShippingMethod(method) {
		const shippingOptions = this.page.locator('.wc-block-components-shipping-rates-control');
		await shippingOptions.getByText(method).click();
	}

	async selectPaymentMethod(method) {
		await this.page.getByText(method, { exact: false }).click();
	}

	async placeOrder() {
		// Try both case variations as different themes may use different casing
		const placeOrderButton = this.page.getByRole('button', { name: /place.*order/i });
		await placeOrderButton.scrollIntoViewIfNeeded();
		await placeOrderButton.click();
	}

	async verifyOrderReceived() {
		// Wait for navigation to order-received page
		await this.page.waitForURL('**/checkout/order-received/**', { timeout: 30000 });
		
		// Also check for the confirmation text
		await expect(
			this.page.getByRole('heading', { name: 'Order received' })
				.or(this.page.getByText(/thank you.*order.*received/i).first())
		).toBeVisible({ timeout: 5000 });
	}

	async verifyShippingDetails(details) {
		const orderDetails = this.page.locator('.wc-block-order-confirmation-shipping-address');
		for (const text of Object.values(details)) {
			await expect(orderDetails).toContainText(text);
		}
	}

	async verifyBillingDetails(details) {
		const orderDetails = this.page.locator('.wc-block-order-confirmation-billing-address');
		for (const text of Object.values(details)) {
			await expect(orderDetails).toContainText(text);
		}
	}

	async addCoupon(code) {
		const addCouponButton = this.page.getByRole('button', { name: /add.*coupon/i });
		if (await addCouponButton.isVisible()) {
			await addCouponButton.click();
			const couponField = this.page.getByPlaceholder(/coupon/i);
			await couponField.fill(code);
			await this.page.getByRole('button', { name: /apply/i }).click();
		}
	}

	async verifyTotals(expectedSubtotal, expectedTotal) {
		if (expectedSubtotal) {
			await expect(
				this.page.locator('.wc-block-components-totals-subtotal .wc-block-components-totals-item__value')
			).toContainText(expectedSubtotal);
		}
		if (expectedTotal) {
			await expect(
				this.page.locator('.wc-block-components-totals-footer-item .wc-block-components-totals-item__value')
			).toContainText(expectedTotal);
		}
	}

	async switchToPickup() {
		await this.page.getByRole('radio', { name: 'Pickup' }).click();
	}

	async switchToShipping() {
		await this.page.getByRole('radio', { name: 'Ship' }).click();
	}

	async selectPickupLocation(location) {
		await this.page.getByRole('radio', { name: location }).click();
	}

	async expandBillingAddress() {
		const useDifferentBilling = this.page.getByRole('checkbox', {
			name: 'Use same address for billing',
		});
		if (await useDifferentBilling.isChecked()) {
			await useDifferentBilling.uncheck();
		}
	}

	async verifyCheckoutBlocks() {
		// Verify blocks-specific elements
		await expect(this.page.locator('.wp-block-woocommerce-checkout, .wc-block-checkout')).toBeVisible();
		await expect(this.page.getByText('Contact information')).toBeVisible();
		await expect(this.page.getByText('Order summary')).toBeVisible();
	}

	async continueToPayment() {
		const continueButton = this.page.getByRole('button', { name: 'Continue to payment' });
		if (await continueButton.isVisible()) {
			await continueButton.click();
		}
	}
}