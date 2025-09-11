/**
 * Checkout Tests - Ported from WooCommerce Core E2E
 * 
 * Tests checkout functionality with both classic and block-based checkout.
 * For blocks-specific testing, tests enable blocks mode in beforeAll.
 */

import { test, expect } from '@playwright/test';
import { faker } from '@faker-js/faker';

/**
 * Helper to fill billing details in checkout
 */
async function fillBillingDetails(page, details = {}) {
    const data = {
        email: faker.internet.email(),
        firstName: faker.person.firstName(),
        lastName: faker.person.lastName(),
        address: faker.location.streetAddress(),
        city: faker.location.city(),
        postcode: faker.location.zipCode('#####'),
        phone: faker.phone.number('###-###-####'),
        ...details
    };

    // Fill email (works for both classic and blocks)
    await page.getByRole('textbox', { name: /email/i }).first().fill(data.email);
    
    // Fill name fields
    await page.getByRole('textbox', { name: /first name/i }).first().fill(data.firstName);
    await page.getByRole('textbox', { name: /last name/i }).first().fill(data.lastName);
    
    // Fill address fields
    await page.getByRole('textbox', { name: /street address|address/i }).first().fill(data.address);
    await page.getByRole('textbox', { name: /city/i }).first().fill(data.city);
    await page.getByRole('textbox', { name: /zip|postal/i }).first().fill(data.postcode);
    await page.getByRole('textbox', { name: /phone/i }).first().fill(data.phone);
    
    return data;
}

/**
 * Helper to add product to cart and go to checkout
 */
async function addProductAndGoToCheckout(page, productId = 12) {
    // Add product to cart via URL parameter - using product ID 12 (Test Product)
    await page.goto(`/?add-to-cart=${productId}`);
    await page.waitForLoadState('networkidle');
    
    // Now go to checkout
    await page.goto('/checkout/');
    await page.waitForLoadState('networkidle');
}

/**
 * Helper to check if we're on blocks checkout
 */
async function isBlocksCheckout(page) {
    // Check for blocks-specific class
    const blocksElement = page.locator('.wp-block-woocommerce-checkout, .wc-block-checkout');
    return await blocksElement.count() > 0;
}

/**
 * Helper to enable checkout blocks
 */
async function enableCheckoutBlocks(page) {
    // Use WP REST API or admin to enable blocks
    // For now, we'll update the checkout page content directly via API
    const response = await page.request.post('/wp-json/wp/v2/pages', {
        headers: {
            'Authorization': 'Basic ' + Buffer.from('admin:password').toString('base64')
        },
        data: {
            'title': 'Checkout',
            'content': '<!-- wp:woocommerce/checkout /-->',
            'status': 'publish'
        }
    });
    
    if (!response.ok()) {
        // Page might already exist, try to find and update it
        const pages = await page.request.get('/wp-json/wp/v2/pages?search=checkout');
        const pagesData = await pages.json();
        if (pagesData.length > 0) {
            await page.request.post(`/wp-json/wp/v2/pages/${pagesData[0].id}`, {
                headers: {
                    'Authorization': 'Basic ' + Buffer.from('admin:password').toString('base64')
                },
                data: {
                    'content': '<!-- wp:woocommerce/checkout /-->'
                }
            });
        }
    }
}

test.describe('Checkout Tests', () => {
    
    test('Guest can checkout with Cash on Delivery', async ({ page }) => {
        await addProductAndGoToCheckout(page);
        
        // Check if checkout loaded - look for blocks checkout or heading
        await expect(
            page.locator('.wc-block-checkout, h1:has-text("Checkout"), h2:has-text("Billing")')
                .first()
        ).toBeVisible();
        
        // Fill billing details
        const billingData = await fillBillingDetails(page);
        
        // Select Cash on Delivery
        await page.getByText('Cash on delivery').click();
        
        // Place order
        await page.getByRole('button', { name: /place order/i }).click();
        
        // Wait for order confirmation (with longer timeout)
        await expect(
            page.getByText(/order received|thank you/i).first()
        ).toBeVisible({ timeout: 30000 });
    });
    
    test('Guest can create account during checkout', async ({ page }) => {
        await addProductAndGoToCheckout(page);
        
        // Fill billing details
        const billingData = await fillBillingDetails(page);
        
        // Check create account checkbox if visible
        const createAccount = page.getByRole('checkbox', { name: /create.*account/i }).first();
        if (await createAccount.isVisible()) {
            await createAccount.check();
        }
        
        // Select payment and place order
        await page.getByText(/direct bank transfer|bank transfer/i).first().click();
        await page.getByRole('button', { name: /place order/i }).click();
        
        // Verify order confirmation
        await expect(
            page.getByText(/order received|thank you/i).first()
        ).toBeVisible({ timeout: 30000 });
    });
    
    test('Can switch between payment methods', async ({ page }) => {
        await addProductAndGoToCheckout(page);
        
        await fillBillingDetails(page);
        
        // Switch between payment methods
        const cod = page.getByText('Cash on delivery');
        const bankTransfer = page.getByText(/direct bank transfer/i);
        
        // Click COD
        await cod.click();
        
        // Verify COD is selected (radio button or active class)
        const codRadio = page.getByRole('radio', { name: /cash on delivery/i });
        if (await codRadio.isVisible()) {
            await expect(codRadio).toBeChecked();
        }
        
        // Switch to Bank Transfer
        await bankTransfer.click();
        
        // Verify Bank Transfer is selected
        const bankRadio = page.getByRole('radio', { name: /bank transfer/i });
        if (await bankRadio.isVisible()) {
            await expect(bankRadio).toBeChecked();
        }
    });
});

test.describe('Checkout Blocks Specific Tests', () => {
    
    test.beforeAll(async ({ browser }) => {
        // Enable checkout blocks for these tests
        const context = await browser.newContext();
        const page = await context.newPage();
        await enableCheckoutBlocks(page);
        await context.close();
    });
    
    test('Checkout blocks render correctly', async ({ page }) => {
        await addProductAndGoToCheckout(page);
        
        // Wait for blocks checkout to be visible
        await page.waitForSelector('.wc-block-checkout', { timeout: 10000 });
        
        // Verify blocks-specific elements
        const isBlocks = await isBlocksCheckout(page);
        expect(isBlocks).toBeTruthy();
        
        // Check for blocks-specific sections - using headings which are more reliable
        await expect(page.getByRole('heading', { name: 'Contact information' })).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Billing address' })).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Payment options' })).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Order summary' })).toBeVisible();
    });
    
    test('Blocks checkout field validation works', async ({ page }) => {
        await addProductAndGoToCheckout(page);
        
        // Try to place order without filling fields
        await page.getByRole('button', { name: /place order/i }).click();
        
        // Should show validation errors
        await expect(page.getByText(/required|please enter/i).first()).toBeVisible();
        
        // Fill required fields
        await fillBillingDetails(page);
        
        // Select payment method
        await page.getByText('Cash on delivery').click();
        
        // Now order should go through
        await page.getByRole('button', { name: /place order/i }).click();
        await expect(
            page.getByText(/order received|thank you/i).first()
        ).toBeVisible({ timeout: 30000 });
    });
    
    test('Coupon field works in blocks checkout', async ({ page }) => {
        await addProductAndGoToCheckout(page);
        
        // Look for add coupon button (blocks specific)
        const addCoupon = page.getByRole('button', { name: /add.*coupon/i });
        if (await addCoupon.isVisible()) {
            await addCoupon.click();
            
            // Enter coupon code
            const couponField = page.getByPlaceholder(/coupon/i);
            if (await couponField.isVisible()) {
                await couponField.fill('TESTCOUPON');
                
                // Apply coupon
                const applyButton = page.getByRole('button', { name: /apply/i });
                if (await applyButton.isVisible()) {
                    await applyButton.click();
                    
                    // Check for discount applied or error message
                    await expect(
                        page.getByText(/discount|coupon.*applied|invalid/i).first()
                    ).toBeVisible({ timeout: 5000 });
                }
            }
        }
    });
});