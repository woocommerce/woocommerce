import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  testDir: './tests',
  forbidOnly: !!process.env.CI,
  retries: 0,
  fullyParallel: false,
  workers: 1,
  // Timeouts from WooCommerce Core test suite
  timeout: 120 * 1000, // 120 seconds per test
  expect: { 
    timeout: process.env.CI ? 20 * 1000 : 10 * 1000 // 10-20 seconds for expect assertions
  },
  reportSlowTests: { max: 5, threshold: 30 * 1000 }, // Report tests slower than 30 seconds
  reporter: [
    ['list'],
    ['html', { open: 'never' }],
    ['playwright-ctrf-json-reporter', {
      outputDir: './results',
      outputFile: 'ctrf.json',
    }],
    ['allure-playwright', {
      resultsDir: './results/allure',
    }],
    ['blob', {
      outputDir: './results/blob',
    }],
  ],
  use: {
    baseURL: process.env.QIT_SITE_URL || 'http://localhost:8080',
    trace: 'on-first-retry',
    // Additional timeouts from WooCommerce Core
    actionTimeout: process.env.CI ? 20 * 1000 : 10 * 1000, // 10-20 seconds for actions
    navigationTimeout: process.env.CI ? 20 * 1000 : 10 * 1000, // 10-20 seconds for navigation
    screenshot: { mode: 'only-on-failure', fullPage: true },
    video: 'retain-on-failure',
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
});
