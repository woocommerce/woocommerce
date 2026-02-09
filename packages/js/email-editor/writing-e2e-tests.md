# Writing E2E Tests for WooCommerce Email Editor

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Test Structure](#test-structure)
3. [Writing Tests](#writing-tests)
4. [Best Practices](#best-practices)
5. [Debugging Tests](#debugging-tests)

## Prerequisites

1. **Environment Setup**:

   ```bash
   # From repository root
   nvm use
   pnpm install
   pnpm --filter='@woocommerce/plugin-woocommerce' build
   cd plugins/woocommerce
   pnpm env:start 
   ```

   or 

   ```bash
   # From anywhere
   pnpm --filter='@woocommerce/plugin-woocommerce' build
   pnpm --filter='@woocommerce/plugin-woocommerce' wp-env start
   ```

2. **Required Dependencies**:
   - Node
   - PNPM
   - Docker
   - WordPress Environment (wp-env)

## Test Structure

### 1. Basic Test File Structure

```javascript
const { test, expect } = require('@playwright/test');
const { ADMIN_STATE_PATH } = require('../../playwright.config');
const { 
    enableEmailEditor,
    disableEmailEditor 
} = require('./helpers/enable-email-editor-feature');
const { 
    accessTheEmailEditor,
    ensureEmailEditorSettingsPanelIsOpened 
} = require('../../utils/email');

test.describe('WooCommerce Email Editor', () => {
    test.use({ storageState: ADMIN_STATE_PATH });

    test.beforeAll(async ({ baseURL }) => {
        await enableEmailEditor(baseURL);
    });

    test.afterAll(async ({ baseURL }) => {
        await disableEmailEditor(baseURL);
    });

    test('Your test name', async ({ page }) => {
        // Test implementation
    });
});
```

### 2. Test Organization

- Place tests in `tests/e2e-pw/tests/email-editor/`
- Use descriptive file names with `.spec.js` extension
- Group related tests using `test.describe()`
- Use helper functions for common operations

## Writing Tests

### 1. Basic Test Structure

```javascript
test('Can perform specific action', async ({ page }) => {
    // Setup
    await accessTheEmailEditor(page, 'New order');

    // Action
    await page.getByRole('button', { name: 'Settings' }).click();

    // Assertion
    await expect(page.locator('.editor-post-status__toggle'))
        .toContainText('Enabled');
});
```

### 2. Common Test Patterns

#### Testing Email Editor Access

```javascript
test('Can access the email editor', async ({ page }) => {
    await accessTheEmailEditor(page, 'New order');
    await page.getByRole('tab', { name: 'Email' }).click();
    await expect(page.locator('.editor-post-card-panel__title'))
        .toContainText('New order');
});
```

#### Testing Email Settings

```javascript
test('Can update email settings', async ({ page }) => {
    await accessTheEmailEditor(page, 'Customer note');
    await page.getByLabel('Email')
        .getByRole('button', { name: 'Settings' })
        .click();
    await ensureEmailEditorSettingsPanelIsOpened(page);
    
    // Update settings
    await page.locator('[data-automation-id="email_subject"]')
        .fill('New Subject');
    
    // Save changes
    await page.getByRole('button', { name: 'Save' }).click();
    
    // Verify changes
    await expect(page.locator('[data-automation-id="email_subject"]'))
        .toContainText('New Subject');
});
```

Note: If updating the WC_Email options, remember to reset to default. This can be done with

```bash
test.afterAll( async ( { baseURL } ) => {
		await resetWCTransactionalEmail( baseURL, EMAIL_ID );
	} );
```

## Best Practices

1. **Test Isolation**:
   - Each test should be independent
   - Use `beforeAll` and `afterAll` for setup/teardown
   - Reset state between tests

2. **Selectors**:
   - Prefer role-based selectors: `getByRole()`
   - Use data attributes: `[data-automation-id="..."]` or `page.getByTestId(...)`
   - Avoid CSS selectors when possible

3. **Assertions**:
   - Use explicit assertions
   - Wait for elements to be ready
   - Verify both state and content

4. **Error Handling**:
   - Add appropriate timeouts
   - Handle async operations properly
   - Clean up resources


## Debugging Tests

1. **Running Tests in Debug Mode**:

   ```bash
   pnpm test:e2e --debug
   ```

2. **Running tests with browser window visible**:

   ```bash
   pnpm test:e2e --headed
   ```

3. **Taking Screenshots**:

   ```javascript
   await page.screenshot({ path: 'debug-screenshot.png' });
   ```

4. **Viewing Test Reports**:

   ```bash
   pnpm test:e2e --ui
   ```

5. **Running a single file test**:

   ```bash
   pnpm test:e2e email-editor-loads.spec
   ```

6. **Running a specific folder**:

   ```bash
   pnpm test:e2e email-editor
   ```

### Useful links

- UI report:  <http://localhost:9323/>
- Local E2E site: <http://localhost:8086/>
- VSCode plugin: <https://marketplace.visualstudio.com/items?itemName=ms-playwright.playwright> [Guide](https://playwright.dev/docs/getting-started-vscode)
- Getting started with Playwright: <https://playwright.dev/docs/writing-tests> 
    - [Locators guide](https://playwright.dev/docs/locators)
    - [Assertions guide](https://playwright.dev/docs/api/class-locatorassertions#locator-assertions-to-contain-class)

