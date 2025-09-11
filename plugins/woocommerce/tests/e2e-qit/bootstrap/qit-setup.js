#!/usr/bin/env node
/**
 * QIT Bootstrap Setup
 * Runs as part of the globalSetup phase to prepare the environment
 */

const { chromium } = require('@playwright/test');
const path = require('path');

// Load test data
const { admin } = require('../test-data/data');

async function globalSetup() {
    console.log('[QIT Setup] Starting WooCommerce E2E test setup...');
    
    const browser = await chromium.launch();
    const context = await browser.newContext();
    const page = await context.newPage();
    
    try {
        // Navigate to site
        const baseURL = process.env.QIT_SITE_URL || process.env.BASE_URL || 'http://localhost:8080';
        await page.goto(baseURL);
        
        // Dismiss any onboarding/setup wizards
        await dismissOnboarding(page);
        
        // Save authentication state for reuse
        await authenticateAdmin(page, baseURL);
        
        console.log('[QIT Setup] Setup complete');
    } catch (error) {
        console.error('[QIT Setup] Setup failed:', error);
        throw error;
    } finally {
        await browser.close();
    }
}

async function dismissOnboarding(page) {
    try {
        // Try to dismiss WooCommerce onboarding
        await page.goto('/wp-admin/admin.php?page=wc-admin');
        
        // Check for onboarding wizard
        const skipButton = page.locator('text="Skip setup"').first();
        if (await skipButton.isVisible({ timeout: 5000 })) {
            await skipButton.click();
            console.log('[QIT Setup] Dismissed onboarding wizard');
        }
    } catch (e) {
        // Onboarding might already be dismissed
    }
}

async function authenticateAdmin(page, baseURL) {
    try {
        // Navigate to admin
        await page.goto(`${baseURL}/wp-admin/`);
        
        // Check if we need to login
        if (page.url().includes('wp-login.php')) {
            await page.fill('#user_login', admin.username);
            await page.fill('#user_pass', admin.password);
            await page.click('#wp-submit');
            await page.waitForURL('**/wp-admin/**');
            console.log('[QIT Setup] Admin authenticated');
        }
        
        // Save auth state
        const stateDir = path.join(__dirname, '..', '.state');
        const fs = require('fs').promises;
        await fs.mkdir(stateDir, { recursive: true });
        
        const cookies = await context.cookies();
        await fs.writeFile(
            path.join(stateDir, 'admin.json'),
            JSON.stringify({ cookies }),
            'utf8'
        );
    } catch (e) {
        console.error('[QIT Setup] Authentication failed:', e);
    }
}

// Run if called directly
if (require.main === module) {
    globalSetup().catch(console.error);
}

module.exports = globalSetup;