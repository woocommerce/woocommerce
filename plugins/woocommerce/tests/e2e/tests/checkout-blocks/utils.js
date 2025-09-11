/**
 * Utility functions for checkout blocks tests
 */

import { faker } from '@faker-js/faker';

/**
 * Add a product to cart via URL
 */
export async function addProductToCart(page, productId, quantity = 1) {
	await page.goto(`/?add-to-cart=${productId}&quantity=${quantity}`);
	await page.waitForLoadState('networkidle');
}

/**
 * Add multiple products to cart
 */
export async function addProductsToCart(page, products) {
	for (const { id, quantity } of products) {
		await addProductToCart(page, id, quantity);
	}
}

/**
 * Go to checkout page
 */
export async function goToCheckout(page) {
	await page.goto('/checkout/');
	await page.waitForLoadState('networkidle');
}

/**
 * Go to cart page
 */
export async function goToCart(page) {
	await page.goto('/cart/');
	await page.waitForLoadState('networkidle');
}

/**
 * Login as customer
 */
export async function loginAsCustomer(page, username = 'customer', password = 'password') {
	await page.goto('/my-account/');
	
	// Check if we need to login
	if (!page.url().includes('my-account/') || await page.locator('form.login').isVisible()) {
		await page.fill('#username', username);
		await page.fill('#password', password);
		await page.click('button[name="login"]');
		await page.waitForLoadState('networkidle');
	}
}

/**
 * Login as admin
 */
export async function loginAsAdmin(page, username = 'admin', password = 'password') {
	await page.goto('/wp-admin/');
	
	// Check if we need to login
	if (page.url().includes('wp-login.php')) {
		await page.getByLabel('Username or Email Address').fill(username);
		await page.locator('#user_pass').fill(password);  // Use ID selector to avoid ambiguity
		await page.getByRole('button', { name: 'Log In' }).click();
		await page.waitForURL('**/wp-admin/**', { timeout: 10000 });
	}
}

/**
 * Logout
 */
export async function logout(page) {
	await page.goto('/my-account/customer-logout/');
	await page.waitForLoadState('networkidle');
}

/**
 * Navigate to admin page
 */
export async function goToAdminPage(page, path) {
	await page.goto(`/wp-admin/${path}`);
	await page.waitForLoadState('networkidle');
}

/**
 * Generate random customer data
 */
export function generateCustomerData() {
	return {
		firstname: faker.person.firstName(),
		lastname: faker.person.lastName(),
		email: faker.internet.email(),
		phone: faker.phone.number('###-###-####'),
		addressfirstline: faker.location.streetAddress(),
		addresssecondline: '',
		city: faker.location.city(),
		postcode: '90210', // Use a valid US ZIP code
		country: 'United States (US)',
		countryKey: 'US',
		state: 'California',
		stateKey: 'CA',
	};
}

/**
 * Enable checkout blocks via API or admin
 */
export async function enableCheckoutBlocks(page) {
	// For QIT environment, checkout blocks should be enabled via setup.sh
	// This is a no-op placeholder that assumes blocks are already enabled
	console.log('Checkout blocks should be enabled via setup.sh or bootstrap');
	
	// Optionally, verify blocks are enabled by visiting checkout
	try {
		await page.goto('/checkout/', { waitUntil: 'domcontentloaded', timeout: 5000 });
		const blocksElement = page.locator('.wp-block-woocommerce-checkout, .wc-block-checkout');
		if (await blocksElement.count() === 0) {
			console.warn('Checkout blocks may not be enabled. Please configure in setup.sh');
		}
	} catch (error) {
		console.warn('Could not verify checkout blocks status:', error.message);
	}
}

/**
 * Disable checkout blocks (revert to classic)
 */
export async function disableCheckoutBlocks(page) {
	// For QIT environment, this should be handled via setup.sh
	// This is a no-op placeholder
	console.log('Checkout blocks configuration should be handled via setup.sh or bootstrap');
}

/**
 * Check if checkout blocks are enabled
 */
export async function isCheckoutBlocksEnabled(page) {
	await goToCheckout(page);
	const blocksElement = page.locator('.wp-block-woocommerce-checkout, .wc-block-checkout');
	return await blocksElement.count() > 0;
}

/**
 * Setup shipping zones and methods via WP CLI
 */
export async function setupShipping(page) {
	// This would normally use WP CLI or API
	// For now, we'll assume shipping is already configured
	console.log('Shipping should be configured via setup.sh');
}

/**
 * Clear cart
 */
export async function clearCart(page) {
	await goToCart(page);
	const removeButtons = page.locator('.remove');
	const count = await removeButtons.count();
	for (let i = count - 1; i >= 0; i--) {
		await removeButtons.nth(i).click();
		await page.waitForTimeout(500);
	}
}

/**
 * Wait for checkout to fully load
 */
export async function waitForCheckoutLoad(page) {
	await page.waitForLoadState('networkidle');
	await page.waitForSelector('.wc-block-checkout__main, .woocommerce-checkout', { state: 'visible' });
}