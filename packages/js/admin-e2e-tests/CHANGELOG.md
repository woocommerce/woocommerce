# Unreleased

-   Add E2E tests to disabled welcome modal #32505

-   Update test for payment task. #32467

-   Increase timeout threshold for payment task. #32605

# 1.0.0

-   Add returned type annotations and remove unused vars. #8020

-   Add E2E tests for checking store currency if it matches the onboarded country. #7712

-   Make unchecking free features more robust. #7761

-   Fix typescript type error in admin-e2e-tests package #7765

-   Add extension deactivation util function addition. #7804

-   Add tests to Subscriptions inclusion. #7804

-   Add missing dependencies. #8349

-   Update all js packages with minor/patch version changes. #8392

-   Add E2E test for checking onboarding tab clickable after going back. #8469
## Breaking changes

-   Update `@types/jest` to v27
-   Update the peer dependency constraint `@typescript-eslint/eslint-plugin` to ^5.
    - eslint-plugin: ban-types no longer reports object by default.


# 0.1.2

-   Add Customers to analytics pages tested #7573
-   Add `waitForTimeout` utility function #7572
-   Update analytics overview tests to allow re-running the tests.

# 0.1.1

-   Allow packages to be built in isolation. #7286
-   Add scope to BACS slotfill #7405
-   Update e2e matcher for tasklist header #7406
-   Update homescreen, utils, payment task, payments setup. #7338
-   Refactor package style builds #7531
-   Updated onboarding tests to include email prefill and move client setup checkbox to business step.
-   Payment task update. #7577
-   Add test cases for the home screen tasklist and activity panels. #7509
-   Add wait for orders text on activity panel. #7550
-   Allow CBD to be optional in business details in E2E. #7675

# 0.1.0

-   Released initial package
