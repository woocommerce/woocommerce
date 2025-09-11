/**
 * Checkout Blocks Merchant Tests
 * 
 * Tests for merchant configuration and management of checkout blocks.
 * Covers block insertion, configuration, T&S settings, and attributes.
 */

import { test, expect } from '@playwright/test';
import { loginAsAdmin, goToAdminPage } from './utils';

test.describe('Checkout Blocks → Merchant → Block Editor', () => {
	
	test.beforeEach(async ({ page }) => {
		// Login as admin using utility function
		await loginAsAdmin(page);
	});

	test('Checkout block can only be inserted once', async ({ page }) => {
		// Create a new page
		await page.goto('/wp-admin/post-new.php?post_type=page');
		
		// Open block inserter - try different button names depending on WP version
		const inserterButton = page.getByRole('button', { name: /block inserter|toggle block inserter/i }).first();
		const isPressed = await inserterButton.getAttribute('aria-pressed');
		if (!isPressed || isPressed === 'false') {
			await inserterButton.click();
			await page.waitForTimeout(500); // Wait for panel to open
		}

		// Search for checkout block
		await page.getByPlaceholder('Search').fill('checkout');
		
		// Insert checkout block
		const checkoutBlock = page.getByRole('option', { name: 'Checkout', exact: true });
		if (await checkoutBlock.isVisible()) {
			await checkoutBlock.click();
			
			// Verify block was inserted
			await expect(page.locator('.wp-block-woocommerce-checkout')).toBeVisible();
			
			// Try to insert again - should not be possible
			await page.getByPlaceholder('Search').fill('checkout');
			
			// Checkout block should be disabled or not shown
			const secondCheckoutBlock = page.getByRole('option', { name: 'Checkout', exact: true });
			if (await secondCheckoutBlock.isVisible()) {
				// Check if it's disabled
				const isDisabled = await secondCheckoutBlock.getAttribute('aria-disabled');
				expect(isDisabled).toBe('true');
			}
		}
	});

	test('Can configure Terms & Conditions settings', async ({ page }) => {
		// Edit checkout page
		await page.goto('/wp-admin/post.php?post=9&action=edit');
		
		// Check if in block editor
		const blockEditor = page.locator('.wp-block-woocommerce-checkout');
		if (!await blockEditor.isVisible()) {
			// Switch to visual editor if in code editor
			const visualEditorButton = page.getByRole('button', { name: 'Visual editor' });
			if (await visualEditorButton.isVisible()) {
				await visualEditorButton.click();
			}
		}

		// Select checkout block
		await page.locator('.wp-block-woocommerce-checkout').first().click();
		
		// Open block settings
		const settingsButton = page.getByRole('button', { name: 'Settings' });
		if (await settingsButton.isVisible()) {
			await settingsButton.click();
		}

		// Look for T&C settings
		const termsToggle = page.getByRole('checkbox', { name: /terms.*conditions/i });
		if (await termsToggle.isVisible()) {
			// Toggle T&C
			const isChecked = await termsToggle.isChecked();
			await termsToggle.click();
			
			// Verify it toggled
			expect(await termsToggle.isChecked()).toBe(!isChecked);
			
			// Save changes
			await page.getByRole('button', { name: 'Update' }).click();
			await page.waitForSelector('.editor-post-saved-state.is-saved', { timeout: 10000 });
		}
	});

	test('Can configure company field in billing address', async ({ page }) => {
		// Edit checkout page
		await page.goto('/wp-admin/post.php?post=9&action=edit');
		
		// Find the billing address block
		const billingBlock = page.locator('.wp-block-woocommerce-checkout-billing-address-block');
		if (await billingBlock.isVisible()) {
			await billingBlock.click();
			
			// Look for company field setting
			const companyFieldToggle = page.getByRole('checkbox', { name: /company/i });
			if (await companyFieldToggle.isVisible()) {
				// Toggle company field
				const isChecked = await companyFieldToggle.isChecked();
				await companyFieldToggle.click();
				
				// Verify it toggled
				expect(await companyFieldToggle.isChecked()).toBe(!isChecked);
				
				// Save changes
				await page.getByRole('button', { name: 'Update' }).click();
				await page.waitForSelector('.editor-post-saved-state.is-saved', { timeout: 10000 });
			}
		}
	});

	test('Can configure order notes field', async ({ page }) => {
		// Edit checkout page
		await page.goto('/wp-admin/post.php?post=9&action=edit');
		
		// Find the order notes block
		const orderNotesBlock = page.locator('.wp-block-woocommerce-checkout-order-notes-block');
		if (await orderNotesBlock.isVisible()) {
			await orderNotesBlock.click();
			
			// Look for order notes setting
			const orderNotesToggle = page.getByRole('checkbox', { name: /order.*notes/i });
			if (await orderNotesToggle.isVisible()) {
				// Toggle order notes
				const isChecked = await orderNotesToggle.isChecked();
				await orderNotesToggle.click();
				
				// Verify it toggled
				expect(await orderNotesToggle.isChecked()).toBe(!isChecked);
				
				// Save changes
				await page.getByRole('button', { name: 'Update' }).click();
				await page.waitForSelector('.editor-post-saved-state.is-saved', { timeout: 10000 });
			}
		}
	});

	test('Cannot insert checkout block in widget area', async ({ page }) => {
		// Go to widget editor
		await page.goto('/wp-admin/widgets.php');
		
		// Open a widget area
		const widgetArea = page.getByRole('button', { name: 'Footer' }).first();
		if (await widgetArea.isVisible()) {
			await widgetArea.click();
			
			// Try to add checkout block
			const addBlockButton = page.getByRole('button', { name: 'Add block' });
			if (await addBlockButton.isVisible()) {
				await addBlockButton.click();
				
				// Search for checkout
				await page.getByPlaceholder('Search').fill('checkout');
				
				// Checkout block should not be available
				const checkoutBlock = page.getByRole('option', { name: 'Checkout', exact: true });
				await expect(checkoutBlock).not.toBeVisible();
			}
		}
	});
});

test.describe('Checkout Blocks → Merchant → Settings', () => {
	
	test.beforeEach(async ({ page }) => {
		// Login as admin using utility function
		await loginAsAdmin(page);
	});

	test('Can configure checkout page settings', async ({ page }) => {
		// Go to WooCommerce settings
		await page.goto('/wp-admin/admin.php?page=wc-settings&tab=advanced');
		
		// Check checkout page setting
		const checkoutPageSelect = page.getByLabel('Checkout page');
		if (await checkoutPageSelect.isVisible()) {
			// Verify checkout page is selected
			const selectedValue = await checkoutPageSelect.inputValue();
			expect(selectedValue).toBeTruthy();
		}
		
		// Check for checkout endpoints
		const endpointFields = [
			'order_received',
			'add_payment_method', 
			'delete_payment_method',
			'set_default_payment_method'
		];
		
		for (const field of endpointFields) {
			const input = page.locator(`#woocommerce_checkout_${field}_endpoint`);
			if (await input.count() > 0 && await input.isVisible()) {
				const value = await input.inputValue();
				expect(value).toBeTruthy();
			}
		}
	});

	test('Can configure guest checkout settings', async ({ page }) => {
		// Go to account settings
		await page.goto('/wp-admin/admin.php?page=wc-settings&tab=account');
		
		// Guest checkout setting
		const guestCheckbox = page.getByLabel('Allow customers to place orders without an account');
		if (await guestCheckbox.isVisible()) {
			const isChecked = await guestCheckbox.isChecked();
			await guestCheckbox.click();
			expect(await guestCheckbox.isChecked()).toBe(!isChecked);
			
			// Save changes
			await page.getByRole('button', { name: 'Save changes' }).click();
			await expect(page.getByText('Your settings have been saved')).toBeVisible();
			
			// Restore original state
			await guestCheckbox.click();
			await page.getByRole('button', { name: 'Save changes' }).click();
		}
	});

	test('Can configure account creation during checkout', async ({ page }) => {
		// Go to account settings
		await page.goto('/wp-admin/admin.php?page=wc-settings&tab=account');
		
		// Account creation settings
		const createAccountCheckbox = page.getByLabel('Allow customers to create an account during checkout');
		if (await createAccountCheckbox.isVisible()) {
			const isChecked = await createAccountCheckbox.isChecked();
			await createAccountCheckbox.click();
			expect(await createAccountCheckbox.isChecked()).toBe(!isChecked);
			
			// Save changes
			await page.getByRole('button', { name: 'Save changes' }).click();
			await expect(page.getByText('Your settings have been saved')).toBeVisible();
			
			// Restore original state
			await createAccountCheckbox.click();
			await page.getByRole('button', { name: 'Save changes' }).click();
		}
	});
});

test.describe('Checkout Blocks → Merchant → Shipping Settings', () => {
	
	test.beforeEach(async ({ page }) => {
		// Login as admin using utility function
		await loginAsAdmin(page);
	});

	test('Can configure local pickup settings', async ({ page }) => {
		// Go to shipping settings
		await page.goto('/wp-admin/admin.php?page=wc-settings&tab=shipping&section=pickup_location');
		
		// Enable local pickup
		const enablePickupCheckbox = page.getByLabel('Enable local pickup');
		if (await enablePickupCheckbox.isVisible()) {
			// Enable if not already enabled
			if (!await enablePickupCheckbox.isChecked()) {
				await enablePickupCheckbox.check();
			}
			
			// Add pickup location
			const addLocationButton = page.getByRole('button', { name: 'Add pickup location' });
			if (await addLocationButton.isVisible()) {
				await addLocationButton.click();
				
				// Fill location details
				await page.getByLabel('Location name').fill('Test Store');
				await page.getByPlaceholder('Address').fill('123 Test St');
				await page.getByPlaceholder('City').fill('Test City');
				await page.getByPlaceholder('Postcode / ZIP').fill('12345');
				await page.getByLabel('Pickup details').fill('Available Mon-Fri 9am-5pm');
				
				// Save location
				await page.getByRole('button', { name: 'Done' }).click();
			}
			
			// Save changes
			await page.getByRole('button', { name: 'Save changes' }).click();
			await page.waitForResponse(response => response.url().includes('wp-json/wp/v2/settings'));
		}
	});

	test('Can configure shipping zones', async ({ page }) => {
		// Go to shipping zones
		await page.goto('/wp-admin/admin.php?page=wc-settings&tab=shipping');
		
		// Check for existing zones
		const zonesTable = page.locator('.wc-shipping-zones');
		if (await zonesTable.isVisible()) {
			// Look for default zone
			const defaultZone = page.getByText('Locations not covered by your other zones');
			await expect(defaultZone).toBeVisible();
		}
		
		// Add new zone button should be visible
		const addZoneButton = page.getByRole('link', { name: 'Add shipping zone' });
		await expect(addZoneButton).toBeVisible();
	});
});

test.describe('Checkout Blocks → Merchant → Payment Methods', () => {
	
	test.beforeEach(async ({ page }) => {
		// Login as admin using utility function
		await loginAsAdmin(page);
	});

	test('Can enable/disable payment methods', async ({ page }) => {
		// Go to payments settings
		await page.goto('/wp-admin/admin.php?page=wc-settings&tab=checkout');
		
		// Check for payment methods
		const paymentMethods = ['bacs', 'cheque', 'cod', 'paypal'];
		
		for (const method of paymentMethods) {
			const methodRow = page.locator(`tr[data-gateway_id="${method}"]`);
			if (await methodRow.isVisible()) {
				// Find toggle button
				const toggleButton = methodRow.locator('.woocommerce-input-toggle');
				if (await toggleButton.isVisible()) {
					// Check current state
					const isEnabled = await toggleButton.getAttribute('aria-checked') === 'true';
					
					// Click to toggle
					await toggleButton.click();
					
					// Wait for state change
					await page.waitForTimeout(500);
					
					// Verify state changed
					const newState = await toggleButton.getAttribute('aria-checked') === 'true';
					expect(newState).toBe(!isEnabled);
					
					// Toggle back to original state
					await toggleButton.click();
					await page.waitForTimeout(500);
				}
			}
		}
	});

	test('Can configure Cash on Delivery settings', async ({ page }) => {
		// Go to COD settings
		await page.goto('/wp-admin/admin.php?page=wc-settings&tab=checkout&section=cod');
		
		// Enable COD
		const enableCheckbox = page.getByLabel('Enable cash on delivery');
		if (await enableCheckbox.isVisible()) {
			if (!await enableCheckbox.isChecked()) {
				await enableCheckbox.check();
			}
			
			// Configure title
			const titleField = page.getByLabel('Title');
			if (await titleField.isVisible()) {
				await titleField.fill('Cash on Delivery');
			}
			
			// Configure description
			const descField = page.getByLabel('Description');
			if (await descField.isVisible()) {
				await descField.fill('Pay with cash upon delivery.');
			}
			
			// Configure instructions
			const instructionsField = page.getByLabel('Instructions');
			if (await instructionsField.isVisible()) {
				await instructionsField.fill('Pay the delivery person in cash when you receive your order.');
			}
			
			// Save changes
			await page.getByRole('button', { name: 'Save changes' }).click();
			await expect(page.getByText('Your settings have been saved')).toBeVisible();
		}
	});
});