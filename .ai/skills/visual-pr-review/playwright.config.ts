import { defineConfig, devices } from '@playwright/test';
import { setupProjects } from '../../../plugins/woocommerce/tests/e2e-pw/playwright.config';

const BASE_URL = process.env.BASE_URL
  || `http://localhost:${process.env.WP_ENV_TESTS_PORT || '8086'}`;

export default defineConfig({
  timeout: 120 * 1000,
  expect: { timeout: 20 * 1000 },
  workers: 1, retries: 1, reporter: [['list']],
  use: {
    baseURL: `${BASE_URL}/`.replace(/\/+$/, '/'),
    channel: 'chrome',
    contextOptions: { reducedMotion: 'reduce' },
    actionTimeout: 20 * 1000, navigationTimeout: 20 * 1000,
    ...devices['Desktop Chrome'],
  },
  projects: [
    ...setupProjects,
    { name: 'visual-canaries', testDir: __dirname,
      testMatch: 'canaries.spec.ts', dependencies: ['site setup'] },
  ],
});
