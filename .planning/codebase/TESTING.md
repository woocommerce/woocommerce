# Testing Patterns

**Analysis Date:** 2026-02-02

## Test Framework

**Runner:**

-   Jest 29.5.x
-   Config: `client/jest.config.js`

**Assertion Library:**

-   Jest built-in matchers
-   `@testing-library/jest-dom` for DOM assertions

**Run Commands:**

```bash
pnpm run test:js              # Run all tests
pnpm run test:js -- status-badge.test.tsx  # Specific file
pnpm run test:js -- --watch   # Watch mode
pnpm run test:js -- --coverage  # Coverage report
```

## Test File Organization

**Location:**

-   Co-located pattern: tests in `test/` subdirectory next to components

**Naming:**

-   Jest tests: `*.test.tsx`, `*.test.ts`, `*.test.js`
-   Playwright E2E: `*.spec.js`

**Structure:**

```
components/
├── status-badge/
│   ├── status-badge.tsx
│   ├── status-badge.scss
│   ├── index.ts
│   └── test/
│       └── status-badge.test.tsx
```

## Test Structure

**Suite Organization:**

```typescript
import { render } from '@testing-library/react';
import { MyComponent } from '../my-component';

describe( 'MyComponent', () => {
	it( 'renders correctly', () => {
		const { getByText } = render( <MyComponent title="Test" /> );
		expect( getByText( 'Test' ) ).toBeInTheDocument();
	} );

	it( 'handles user interaction', async () => {
		const user = userEvent.setup();
		const mockHandler = jest.fn();
		const { getByRole } = render( <MyComponent onClick={ mockHandler } /> );

		await user.click( getByRole( 'button' ) );
		expect( mockHandler ).toHaveBeenCalled();
	} );
} );
```

**Patterns:**

-   `describe` blocks for component/module grouping
-   `it` for individual test cases
-   Descriptive test names explaining expected behavior
-   Arrange-Act-Assert pattern

## Mocking

**Framework:** Jest built-in mocking

**Patterns:**

```typescript
// Module mocking
jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );

// WordPress data mocking
jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn(),
	useDispatch: jest.fn().mockImplementation( () => ( {
		updateOptions: jest.fn(),
		installAndActivatePlugins: jest.fn(),
	} ) ),
} ) );

// Component mocking
jest.mock( '@woocommerce/components', () => ( {
	EllipsisMenu: ( { renderContent: Content } ) => <Content />,
	List: ( { items } ) => (
		<div>
			{ items.map( ( item ) => (
				<div key={ item.key }>{ item.title }</div>
			) ) }
		</div>
	),
} ) );
```

**What to Mock:**

-   External dependencies (`@wordpress/*`, `@woocommerce/*`)
-   API calls and network requests
-   Complex components when testing parents
-   Browser APIs (window, document)

**What NOT to Mock:**

-   The component under test
-   Simple utility functions
-   Core React functionality

## Fixtures and Factories

**Test Data:**

```typescript
// Mock data inline
const mockPaymentGateway = {
	title: 'Test Gateway',
	id: 'test-gateway',
	plugins: [ 'test-plugin' ],
};

// Reusable fixtures
const createMockOrder = ( overrides = {} ) => ( {
	id: 1,
	status: 'pending',
	total: '100.00',
	...overrides,
} );
```

**Location:**

-   Inline for simple test data
-   Separate files for shared fixtures: `test/fixtures/`
-   Factory functions for complex objects

## Coverage

**Requirements:** None enforced

**View Coverage:**

```bash
pnpm run test:js -- --coverage
```

## Test Types

**Unit Tests:**

-   Component rendering and props
-   User interactions
-   State changes and hooks
-   Utility functions

**Integration Tests:**

-   Component interactions
-   Data flow through components
-   Redux/WordPress data integration

**E2E Tests:**

-   Playwright for end-to-end tests
-   Located in `tests/e2e-pw/`
-   Separate test runner and config

## Common Patterns

**Async Testing:**

```typescript
// Using @testing-library/user-event
it( 'handles async actions', async () => {
	const user = userEvent.setup();
	render( <MyComponent /> );

	await user.click( screen.getByRole( 'button' ) );
	await waitFor( () => {
		expect( screen.getByText( 'Success' ) ).toBeInTheDocument();
	} );
} );
```

**Error Testing:**

```typescript
// Test error states
it( 'displays error message', () => {
	render( <MyComponent hasError errorMessage="Failed to load" /> );

	expect( screen.getByRole( 'alert' ) ).toHaveTextContent( 'Failed to load' );
} );

// Test error boundaries
it( 'catches component errors', () => {
	const spy = jest.spyOn( console, 'error' ).mockImplementation();

	expect( () => {
		render( <BrokenComponent /> );
	} ).toThrow();

	spy.mockRestore();
} );
```

---

_Testing analysis: 2026-02-02_
