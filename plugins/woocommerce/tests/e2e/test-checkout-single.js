import { chromium } from 'playwright';

(async () => {
    const browser = await chromium.launch({ headless: false });
    const page = await browser.newPage();
    
    console.log('Testing Checkout Blocks...');
    
    try {
        // Navigate to checkout with product in cart
        console.log('1. Adding product to cart and navigating to checkout...');
        await page.goto('http://localhost:32777/checkout/?add-to-cart=13&quantity=1');
        await page.waitForTimeout(2000);
        
        // Check if checkout blocks loaded
        console.log('2. Checking for checkout blocks...');
        const checkoutBlock = await page.locator('.wp-block-woocommerce-checkout').first();
        
        if (await checkoutBlock.isVisible()) {
            console.log('✅ Checkout blocks are loaded!');
            
            // Take screenshot
            await page.screenshot({ path: 'checkout-blocks-loaded.png' });
            console.log('📸 Screenshot saved as checkout-blocks-loaded.png');
            
            // Check for email field
            const emailField = await page.getByRole('textbox', { name: /email/i }).first();
            if (await emailField.isVisible()) {
                console.log('✅ Email field is visible');
            }
            
            // Check for payment methods
            const codPayment = await page.getByText('Cash on delivery').first();
            if (await codPayment.isVisible()) {
                console.log('✅ Cash on Delivery payment method is available');
            }
            
        } else {
            console.log('❌ Checkout blocks NOT found - page may be using classic checkout');
        }
        
    } catch (error) {
        console.error('Error during test:', error);
    } finally {
        await browser.close();
    }
})();