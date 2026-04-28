# Development

## Local Development

The most efficient way to develop the Email Editor is by using the WooCommerce plugin's watch command:

```bash
pnpm --filter='@woocommerce/plugin-woocommerce' watch:build:admin
```

---

## Translation text domain

Translation function calls inside the package (`__()`, `_x()`, `_n()`, `_nx()`) use the `__i18n_text_domain__` identifier as the text domain argument instead of a hardcoded string literal. This lets each consumer of the package (WooCommerce, MailPoet, or any other plugin) substitute its own text domain at bundle time and extract strings under that domain with `wp i18n make-pot`.

If the identifier is not substituted, the package falls back to `'woocommerce'` at runtime (assigned in `src/index.ts`) so the editor still loads and renders with English strings — matching the package's pre-1.11 behaviour. Consumers that want their own translations to apply **should** replace the identifier with a string literal during their own build, typically with [`webpack.DefinePlugin`](https://webpack.js.org/plugins/define-plugin/):

```js
// consumer webpack.config.js
const webpack = require( 'webpack' );

module.exports = {
	// …
	plugins: [
		new webpack.DefinePlugin( {
			__i18n_text_domain__: JSON.stringify( 'your-text-domain' ),
		} ),
	],
};
```

String extraction happens against the built consumer bundle (not the package source), so `wp i18n make-pot` picks up the substituted literal domain and extracts strings correctly for the consumer's translation workflow. Without the substitution, strings stay under the `woocommerce` domain at runtime and `wp i18n make-pot` won't extract them under the consumer's own domain — translators won't be able to translate them under that domain even though the editor still works.

### Jest tests

Jest does not run through webpack, so `DefinePlugin` does not apply to unit tests that import from this package. Either rely on the runtime fallback (strings will use `woocommerce`) or define the identifier explicitly in the consumer's Jest setup file:

```js
// jest.setup.js / global-mocks.js
globalThis.__i18n_text_domain__ = 'your-text-domain';
```

---

## Running Tests

### JavaScript Component Tests

To run component tests in the JS package:

```bash
pnpm run test:js
```

To run a specific test file:

```bash
pnpm run test:js -- src/components/my-component/test/my-component.spec.tsx
```

We use [Jest](https://jestjs.io/) with `@testing-library/react`. These are **component tests**, not strict unit tests, and include mocked dependencies.

#### Guidelines for Writing Component Tests

- Use the `should` prefix in test names (e.g., `should render the modal`).
- Avoid testing implementation details. Prefer visible DOM assertions.
- Use [jest.fn()](https://jestjs.io/docs/mock-functions) and [jest.mock()](https://jestjs.io/docs/manual-mocks) for mocking.
- Always mock required dependencies **before** importing the tested component.
- Avoid writing tests for components that are simple wrappers of 3rd-party libraries (e.g., just rendering a WordPress component without added logic).
- Prefer reusable mocks for commonly used packages (e.g., `@wordpress/data`, `@wordpress/components`). Create shared mock setup files when possible.
- Use descriptive `data-testid` attributes when using them in mocked components (e.g., `data-testid="modal"`).
- Use `screen.getByRole()`, `getByText()`, or similar accessible queries where applicable.

#### Mocking

- Keep all mocks close to the test, unless reused in multiple tests.
- Use shared mock setup files (e.g., `__mocks__/setup-shared-mocks.ts`) to keep individual test files clean.
- Use `jest.mock()` for external dependencies like WordPress packages or internal modules.
- When mocking component props, prefer `React.ComponentProps<'button'>` or specific prop interfaces to avoid `any`.

Example for reusable mock setup:

```ts
// __mocks__/setup-shared-mocks.ts
jest.mock('@wordpress/data', () => ({
  useSelect: jest.fn(),
  useDispatch: jest.fn(),
  createRegistrySelector: jest.fn(),
}));
```

#### Example Basic Component Test

```tsx
/* eslint-disable @woocommerce/dependency-group -- because we import mocks first, we deactivate this rule to avoid es lint errors */
import '../../__mocks__/setup-shared-mocks';

/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';
import '@testing-library/jest-dom';

/**
 * Internal dependencies
 */
import { MyComponent } from '../my-component';

describe('MyComponent', () => {
  it('should render a button and respond to click', () => {
    const onClickMock = jest.fn();
    render(<MyComponent onClick={onClickMock} />);
    const button = screen.getByRole('button', { name: /click me/i });
    expect(button).toBeInTheDocument();
    fireEvent.click(button);
    expect(onClickMock).toHaveBeenCalled();
  });
});
```

---

### E2E Tests

E2E tests: [writing-e2e-tests.md](../../../packages/js/email-editor/writing-e2e-tests.md)
