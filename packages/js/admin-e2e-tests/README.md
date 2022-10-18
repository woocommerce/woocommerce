# Admin E2E Tests

An end-to-end test suite for WooCommerce setup, onboarding, home screen/task list, and analytics. 

## Installation

Install the module

```bash
pnpm install @woocommerce/admin-e2e-tests --save
```

## Usage

Create a E2E test specification file under `/tests/e2e/specs/example.test.js`:

```js
import { testAdminBasicSetup } from '@woocommerce/admin-e2e-tests';

testAdminBasicSetup();
```

See the [wooCommerce E2E Boilerplate](https://github.com/woocommerce/woocommerce-e2e-boilerplate) for instructions on setting up an E2E test environment.

### Configuration

Add the following entries to `tests/e2e/config/default.json`

```json
  "onboardingwizard": {
    "industry": "Test industry",
    "numberofproducts": "1 - 10",
    "sellingelsewhere": "No"
  },
  "settings": {
    "shipping": {
      "zonename": "United States",
      "zoneregions": "United States (US)",
      "shippingmethod": "Free shipping"
    }
  }
```

### Available tests

The following test functions are included in the package:

| Function | Description |
| --- | --- |
| `testAdminBasicSetup` | Test that WooCommerce can be activated with pretty permalinks |
| `testAdminOnboardingWizard` | Complete the onboarding wizard with US merchant |
| `testAdminNonUSRecommendedFeatures` | Complete the onboarding wizard with non-US merchant |
| `testSelectiveBundleWCPay` | Ensure onboarding wizard offers WC Payments in appropriate contexts |
| `testAdminAnalyticsPages` | Test that the React App is functional on Analytics pages |
| `testAdminCouponsPage` | Test that the Coupons is functional |
| `testAdminPaymentSetupTask` | Test that payment methods can be configured |
