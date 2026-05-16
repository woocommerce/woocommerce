import { test, type Page } from '@playwright/test';
import fs from 'fs';
import path from 'path';
import {
  ADMIN_STATE_PATH,
  CUSTOMER_STATE_PATH,
} from '../../../plugins/woocommerce/tests/e2e-pw/playwright.config';

type Canary = {
  name: string; path: string; auth: 'admin'|'customer'|'none';
  description: string; before?: 'addProductToCart';
};
const CANARIES: Canary[] = JSON.parse(
  fs.readFileSync(path.join(__dirname, 'canary-urls.json'), 'utf-8')
).canaries;
const SCREENSHOT_DIR = process.env.VISUAL_REVIEW_OUTPUT_DIR
  || path.join(__dirname, 'screenshots');
fs.mkdirSync(SCREENSHOT_DIR, { recursive: true });

async function settle(page: Page) {
  await page.waitForLoadState('networkidle', { timeout: 30_000 }).catch(()=>{});
  await page.evaluate(() => document.fonts?.ready).catch(()=>{});
  await page.waitForTimeout(500);
}
async function addProductToCart(page: Page) {
  await page.goto('/shop/');
  await page.locator('.add_to_cart_button, .wp-block-button__link')
    .filter({ hasText: /add to cart/i }).first().click().catch(()=>{});
  await page.waitForTimeout(1000);
}

for (const c of CANARIES) {
  const storageState = c.auth === 'admin' ? ADMIN_STATE_PATH
    : c.auth === 'customer' ? CUSTOMER_STATE_PATH : undefined;
  test.describe(c.name, () => {
    test.use({ storageState });
    test(`screenshot ${c.name}`, async ({ page }) => {
      if (c.before === 'addProductToCart') await addProductToCart(page);
      await page.goto(c.path);
      await settle(page);
      await page.screenshot({
        path: path.join(SCREENSHOT_DIR, `${c.name}.png`), fullPage: true
      });
    });
  });
}
