/**
 * Simple test to verify checkout blocks are working
 */

import { test, expect } from '@playwright/test';

test.describe('Simple Checkout Blocks Test', () => {
	test('Can access checkout page with blocks', async ({ page }) => {
		// Add a product to cart
		await page.goto('/?add-to-cart=11');
		await page.waitForLoadState('networkidle');
		
		// Go to checkout
		await page.goto('/checkout/');
		await page.waitForLoadState('networkidle');
		
		// Verify checkout blocks are present
		await expect(page.locator('.wp-block-woocommerce-checkout')).toBeVisible();
		
		// Verify main sections - use more specific selectors
		await expect(page.getByRole('heading', { name: 'Contact information' })).toBeVisible();
		await expect(page.getByRole('heading', { name: 'Billing address' })).toBeVisible();
		await expect(page.getByRole('heading', { name: 'Payment options' })).toBeVisible();
		await expect(page.getByRole('heading', { name: 'Order summary' })).toBeVisible();
	});
	
	test('Can fill checkout form and place order', async ({ page }) => {
		// Add a product to cart
		await page.goto('/?add-to-cart=11');
		await page.waitForLoadState('networkidle');
		
		// Go to checkout  
		await page.goto('/checkout/');
		await page.waitForLoadState('networkidle');
		
		// Fill email
		await page.getByLabel('Email address').fill('test@example.com');
		
		// Fill billing details - be more specific with selectors
		await page.getByRole('textbox', { name: 'First name' }).fill('John');
		await page.getByRole('textbox', { name: 'Last name' }).fill('Doe');
		await page.getByRole('textbox', { name: 'Address', exact: true }).fill('123 Test Street');
		await page.getByRole('textbox', { name: 'City' }).fill('New York');
		await page.getByRole('textbox', { name: 'ZIP Code' }).fill('10001');
		
		// Select Cash on Delivery
		await page.getByText('Cash on delivery').click();
		
		// Place order
		await page.getByRole('button', { name: 'Place Order' }).click();
		
		// Wait for order confirmation - use first match
		await expect(
			page.getByText(/thank you|order received/i).first()
		).toBeVisible({ timeout: 30000 });
	});
});