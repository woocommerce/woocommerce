# E2E Guidelines <!-- omit in toc -->

## Table of contents <!-- omit in toc -->

-   [Structure](#structure)
-   [Playwright](#playwright)
    -   [Structure](#structure-1)

This living document serves to prescribe coding guidelines specific to the WooCommerce Blocks project E2E tests. For more information on how to run Playwright end-to-end (E2E) tests, please refer to the [dedicated resource](../../tests/e2e/README.md).

## Structure

There are two folders dedicated to E2E tests.

The first folder is named "e2e-jest" and it contains all the E2E tests that were created with the deprecated infrastructure Jest + Puppetter. The "e2e" folder contains all the E2E tests that were created with the current infrastructure: Playwright. These tests are actively maintained and should be used for all new E2E testing.

### Playwright

#### Structure

There are three Playwright projects configuration:

-   blockTheme
-   blockThemeWithGlobalSideEffects
-   classicTheme

The blockTheme project runs the tests with the suffix _block_theme_. In this case, the theme is a block theme. The block theme is the default WordPress theme. Currently, it is Twenty-Twenty Three. You should use this configuration if you want test the block with the Site Editor.

The blockThemeWithGlobalSideEffects project runs the tests with the suffix _block_theme.side_effects_. These tests have side effects that can potentially impact other end-to-end (E2E) tests. Due to the nature of these tests and their potential impact, they are not executed in parallel with other tests.

The classicTheme project runs the tests with the suffix _classic_theme_. In this case, the theme is a Twenty Twenty-One. You should use this configuration if you want test the block with a classic theme.

Each block should have a dedicated folder with a scoped util file if you want share some logic related to the block.

#### Code Guidelines

##### Make tests as isolated as possible - Avoid side effects

Each test should be completely isolated from another test and should run independently with its own local storage, session storage, data, cookies etc. Test isolation improves reproducibility, makes debugging easier and prevents cascading test failures.

In order to avoid repetition for a particular part of your test you can use before and after hooks. Within your test file add a before hook to run a part of your test before each test such as going to a particular URL or logging in to a part of your app. This keeps your tests isolated as no test relies on another. However it is also ok to have a little duplication when tests are simple enough especially if it keeps your tests clearer and easier to read and maintain. Avoid using functions that impact other tests, such as the `deleteAllTemplates` function, which restores all templates and can break other tests since E2E tests run in parallel. After running a suite of tests for a specific block, it is important to clean up any changes made during the tests to ensure a clean slate for subsequent test runs.

For more detail see [Make Tests as Isolated as Possible](https://playwright.dev/docs/best-practices#make-tests-as-isolated-as-possible).

##### Use Locators

In order to write end to end tests we need to first find elements on the webpage. We can do this by using Playwright's built in locators. Locators come with auto waiting and retry-ability. Auto waiting means that Playwright performs a range of actionability checks on the elements, such as ensuring the element is visible and enabled before it performs the click. To make tests resilient, we recommend prioritizing user-facing attributes and explicit contracts. For more detail see [Use Locators](https://playwright.dev/docs/best-practices#use-locators).

##### Avoid Using Relative Imports

In order to make the codebase cleaner, you should import the function from the packages:

-   "@woocommerce/e2e-utils": Contains generic utils for interactive with the page.
-   "@woocommerce/e2e-types": Contains generic types.
-   "@woocommerce/e2e-playwright-utils": Contains utils for playwright for example custom hooks.

By using these packages, you can make your code more modular and easier to maintain.

<!-- FEEDBACK -->

---

[We're hiring!](woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-blocks/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./docs/contributors/e2e-guidelines.md)

<!-- /FEEDBACK -->

