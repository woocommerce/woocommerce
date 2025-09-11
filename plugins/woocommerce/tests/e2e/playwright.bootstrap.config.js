import { defineConfig } from '@playwright/test';
import baseConfig from './playwright.config.js';

export default defineConfig({
  ...baseConfig,
  testDir: './bootstrap',
  testMatch: '*.spec.js',
  projects: [
    {
      name: 'bootstrap',
      use: { ...baseConfig.use },
    },
  ],
  reporter: [['list']],
});