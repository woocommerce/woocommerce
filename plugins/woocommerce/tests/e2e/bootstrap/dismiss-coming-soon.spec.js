import { test, expect } from '@playwright/test';

test('Dismiss WooCommerce coming soon mode', async ({ page }) => {
  // Navigate to WordPress admin login
  await page.goto('/wp-admin');
  
  // Login with admin credentials
  await page.getByRole('textbox', { name: 'Username or Email Address' }).fill('admin');
  await page.getByRole('textbox', { name: 'Password' }).fill('password');
  await page.getByRole('button', { name: 'Log In' }).click();
  
  // Wait for dashboard to load
  await page.waitForURL('**/wp-admin/**');
  
  // Navigate to WooCommerce Site Visibility settings
  await page.goto('/wp-admin/admin.php?page=wc-settings&tab=site-visibility');
  
  // Check if coming soon mode is active and switch to Live if needed
  const liveRadio = page.getByRole('radio', { name: 'Live' });
  const isLiveSelected = await liveRadio.isChecked();
  
  if (!isLiveSelected) {
    // Click on Live radio button
    await liveRadio.click();
    
    // Save the changes
    await page.getByRole('button', { name: 'Save changes' }).click();
    
    // Wait for confirmation message
    await expect(page.getByText('Your settings have been saved.')).toBeVisible();
    
    console.log('Successfully dismissed coming soon mode - site is now live');
  } else {
    console.log('Site is already live - no changes needed');
  }
  
  // Verify the site is live by checking the toolbar
  await expect(page.getByRole('menuitem', { name: 'Live' })).toBeVisible();
});