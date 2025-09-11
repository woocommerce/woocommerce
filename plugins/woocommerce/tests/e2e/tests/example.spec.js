import { test, expect } from '@playwright/test';

test('site is reachable and has a body', async ({ page }) => {
  const response = await page.goto('/');
  expect(response?.status()).toBe(200);

  await expect(page.locator('body')).toBeVisible();
});