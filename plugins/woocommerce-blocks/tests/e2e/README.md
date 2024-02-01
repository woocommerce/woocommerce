# WooCommerce Blocks Playwright End to End Tests

This is the documentation for the new E2E testing setup based on Playwright and wp-env. Over time, these playwright E2E tests should replace the current [Puppeteer E2E tests](../e2e-jest/).

## Table of contents <!-- omit in toc -->

-   [Pre-requisites](#pre-requisites)
-   [Introduction](#introduction)
    -   [Running tests for the first time](#running-tests-for-the-first-time)
    -   [To run the test again, re-create the environment to start with a fresh state](#to-run-the-test-again-re-create-the-environment-to-start-with-a-fresh-state)
    -   [Other ways of running tests](#other-ways-of-running-tests)

## Pre-requisites

-   Node.js ([Installation instructions](https://nodejs.org/en/download/))
-   NVM ([Installation instructions](https://github.com/nvm-sh/nvm))
-   Docker and Docker Compose ([Installation instructions](https://docs.docker.com/engine/install/))

Note, that if you are on Mac and you install docker through other methods such as homebrew, for example, your steps to set it up might be different. The commands listed in steps below may also vary.

If you are using Windows, we recommend using [Windows Subsystem for Linux (WSL)](https://docs.microsoft.com/en-us/windows/wsl/) for running E2E tests. Follow the [WSL Setup Instructions](../tests/e2e-jest/WSL_SETUP_INSTRUCTIONS.md) first before proceeding with the steps below.

## Introduction

End-to-end tests are powered by Playwright. The test site is spun up using `wp-env` (recommended), but we will continue to support `e2e-environment` in the meantime.

### Running tests for the first time

In the root directory, run:

```sh
nvm use
```

```sh
pnpm install
```

Now change directory to `plugins/woocommerce-blocks/`:

```sh
cd plugins/woocommerce-blocks/
```

```sh
pnpm run env:start
```

```sh
pnpm run test:e2e
```

### To run the test again, re-create the environment to start with a fresh state

```sh
pnpm run env:restart
```

```sh
pnpm run test:e2e
```

### Tests with side effects

We call tests that affect other tests (ones that modify the site settings, using
custom plugins) are tests with side effects and we
[split](https://github.com/woocommerce/woocommerce-blocks/pull/10508) those
tests to a separate test suite:

```sh
pnpm run test:e2e:side-effects
```

_Note: All command parameters of `test:e2e` can be used for
`test:e2e:side-effects`._

### Tests with a classic theme and a block theme with custom templates

By default, e2e tests run in a non-customized block theme. However, we also have
some e2e tests which run specifically in a classic theme and in a block theme
with custom templates. They can be run like this:

```sh
pnpm run test:e2e:classic-theme
```

```sh
pnpm run test:e2e:block-theme-with-templates
```

\_Note: All command parameters of `test:e2e` can be used for these commands too.

### Other ways of running tests

Headless mode:

```sh
pnpm run test:e2e
```

Interactive UI mode:

```sh
pnpm run test:e2e --ui
```

Headed mode:

```sh
pnpm run test:e2e --headed
```

Debug mode:

```sh
pnpm run test:e2e --debug
```

Running a single test:

```sh
pnpm run test:e2e ./tests/e2e/tests/example.spec.ts
```

To see all options, run the following command:

```sh
npx playwright test --help
```

### Generating dynamic posts to test block variations

Testing a single block can be daunting considering all the different attribute combinations that could be
considered valid for a single block. The basic templating system available in this test suite allows for
the generation of dynamic posts that can be used to test block variations.

Templates use the Handlebars templating system and you can put them anywhere. It's simplest to co-locate them
with the test. You can easily pass custom attributes to a block in your template using the wp-block helper
we've defined.

It looks like this in the template:

```
{{#> wp-block name="woocommerce/featured-category" attributes=attributes /}}
    You can nest content here if you want to test the block with some content.
{{/wp-block}}
```

In your tests you can use `createPostFromTemplate` to create a post containing your template. If you use it
more than once in your test you may want to create it in `beforeAll` as its a slow operation to do many times.

```js
let defaultBlockPost: GeneratedPost;
let customAttributesPost: GeneratedPost;

test.beforeAll( async () => {
	defaultBlockPost = await createPostFromTemplate(
		'Product Filter Stock Status Block',
		TEMPLATE_PATH,
		// data to pass to the template
		{}
	);

	customAttributesPost = await createPostFromTemplate(
		'Product Filter Stock Status Block Dropdown',
		TEMPLATE_PATH,
		// data to pass to the template
		{
			attributes: {
				displayStyle: 'dropdown',
			},
		}
	);
} );
```

In your test you can navigate to the page and then in `afterAll` you can clean it up.

```js
test( 'Test the block', async ( { page } ) => {
	await page.goto( defaultBlockPost.url );
	// do your tests here
} );

test.afterAll( async () => {
	await defaultBlockPost.delete();
} );
```
