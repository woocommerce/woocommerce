# When to employ end to end (E2E) testing

We use [Playwright](https://playwright.dev/) to run tests in a real browser, these are called End to End tests. These tests are fairly expensive to run and often fail randomly due to flaky browser behaviour, with this in mind, we should be careful about when we use them.

Ultimately, the front-end is a representation of our application's state, and in most cases we will be able to reliably determine that it is behaving the way we want it to without setting up a full browser environment to verify this. For example, if we want to test that the quantity of an item in the cart is increased when the `+` button is pressed, we can mock the store and ensure the correct action is dispatched, we can also mock the function used to make requests to the API and test that it is called with the correct parameters. We can then use PHP tests to ensure the API returns the correct response, and then test the cart to ensure receiving new cart data results in an updated DOM (with the new quantity in the quantity selector!)

If the functionality you're testing relies on a third party library whose functions would be difficult to mock, then setting it up as an E2E test would be a good way to check your code is working as intended. An example of this is testing how our blocks behave in the Gutenberg block editor. It would be too difficult to mock all of the interfaces of Gutenberg that would be required to test our blocks.

If the functionality you're testing doesn't rely on a browser function (e.g. the browser back/forward buttons, or local storage) then you may be able to forego using an E2E test and instead write a unit test or integration test using [React Testing Library](https://testing-library.com/docs/react-testing-library/intro/).

An example of things that _should_ be tested with E2E tests:

1.  Blocks cannot be added to the block editor more than once. Reason: **We cannot really mock the Gutenberg functionality to test that this happens without some serious effort.**
2.  Fresh cart data is fetched when using the browser's back buttons. Reason: **We need to emulate the behaviour of a browser when the back button is pressed and this can't be done in unit tests.**
3.  The compatibility notice is shown when first adding the checkout block. Reason: **same as 1**
