# Claude Code Documentation for WooCommerce Settings Payments

**Scope**: Payment gateway UI architecture and patterns
**Location**: `plugins/woocommerce/client/admin/client/settings-payments`

**See also:**

- `../CLAUDE.md` - Testing, linting, and build commands
- `packages/js/data/src/payment-settings/` - Data layer and types

## Quick Workflow

**Modifying payment gateway features (data + UI must be updated together):**

1. Update types: `packages/js/data/src/payment-settings/types.ts`
2. Update test stubs: `packages/js/data/src/payment-settings/test/helpers/stub.ts`
3. Update UI: `client/settings-payments/components/`
4. Test: `cd ../.. && pnpm run test:js -- settings-payments && pnpm run ts:check`

## Architecture

### Data/UI Separation (Critical Pattern)

**Data layer** (must be updated first):

- Types: `packages/js/data/src/payment-settings/types.ts`
- Test stubs: `packages/js/data/src/payment-settings/test/helpers/stub.ts`
- Key types: `PaymentGatewayProvider`, `PaymentsProviderOnboardingState`, `OfflinePaymentMethodProvider`

**UI layer** (depends on data layer):

- Components: `client/settings-payments/components/`
- Tests: Component-level tests in `test/` subdirectories

### Directory Structure

```text
settings-payments/
├── components/               # UI components
│   ├── buttons/             # Action buttons (CompleteSetup, Enable, etc.)
│   ├── status-badge/        # Status badge with popover support
│   ├── payment-gateway-list-item/  # Main list item orchestrator
│   └── ...
├── onboarding/              # Onboarding flows
│   └── providers/           # Provider-specific onboarding
└── utils/                   # Utility functions
```

## Key Patterns

### Security: Disabled/Unsupported Features

When features are disabled or unsupported, use **minimal props** to avoid exposing sensitive actions:

- Placeholder URLs for disabled links (`onboardingHref="#"`)
- No-op functions for callbacks (`setOnboardingModalOpen={() => {}}`)
- Conditionally spread sensitive props only when enabled (use spread operator pattern)
- Explicitly set `disabled={true}`

**Pattern for conditional props**:

```tsx
<Component
	{ ...baseProps }
	href={ enabled ? realUrl : '#' }
	disabled={ ! enabled }
	{ ...( enabled && {
		sensitiveActionProp,
		otherSensitiveProp,
	} ) }
/>
```

**Rationale**:

- Prevents inadvertent triggering of onboarding actions even if button is somehow activated
- Placeholder `#` is safer than empty string `""` if disabled state is bypassed
- Spread operator ensures sensitive props are only present when feature is enabled

### Component Architecture Pattern

**Status determination priority** (in `PaymentGatewayListItem.determineGatewayStatus()`):

1. not_supported → needs_setup → test states → active/inactive

**Button pattern**:

- Accept `gatewayProvider` prop for gateway data
- Support `disabled` prop (combines with internal states)
- Record analytics events on interactions

**Status badge pattern**:

- Supports `popoverContent` for additional context messages
- Located in `components/status-badge/`

### REST API Integration

**Endpoint**: `/wc-admin/settings/payments/providers`

**Key fields**:

- `onboarding.state.supported` - Whether onboarding is supported
- `onboarding.messages.not_supported` - Why not supported (shown in popover)
- `state.account_connected` - Account connection status
- `state.enabled` - Gateway enabled status

## Testing Patterns

### Component Test Structure

Tests follow the pattern: `components/[component-name]/test/[component-name].test.tsx`

**Example**: `components/payment-gateway-list-item/test/payment-gateway-list-item.test.tsx`

### Import Order (Critical)

```typescript
/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import type {
	PaymentGatewayProvider,
	// ... other types
} from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { ComponentName } from '../component-name';
```

**Note**: Type imports from `@woocommerce/data` are external dependencies, not internal.

### Mock Data Pattern

Create a helper function to generate mock gateway data:

```typescript
const createMockGateway = (
	overrides: Partial< PaymentGatewayProvider > = {}
): PaymentGatewayProvider => {
	return {
		// Default values for all required fields
		id: 'test-gateway',
		_order: 1,
		_type: 'gateway' as PaymentsProviderType,
		title: 'Test Gateway',
		icon: 'https://example.com/icon.png', // Always use HTTPS
		// ... more defaults
		...overrides, // Allow test-specific overrides
	};
};
```

**Benefits**:

- Single source of truth for test data
- Easy to create variations with `overrides`
- Keeps tests focused on what's being tested

**Important**: Always use HTTPS URLs in mock data for security best practices.

### Type Import Workaround

To avoid runtime errors when importing enums from `@woocommerce/data`:

```typescript
import type { PaymentsProviderType } from '@woocommerce/data';

// Define enum value as const to avoid runtime import
const PaymentsProviderTypeGateway = 'gateway' as const;

// Use in mock data
_type: PaymentsProviderTypeGateway as PaymentsProviderType,
```

### Mocking Dependencies

Mock all child components and external dependencies with proper TypeScript types:

```typescript
jest.mock( '~/settings-payments/components/status-badge', () => ( {
	StatusBadge: ( {
		status,
		popoverContent,
	}: {
		status: string;
		popoverContent?: React.ReactNode;
	} ) => (
		<div data-testid="status-badge" data-status={ status }>
			{ popoverContent && <div data-testid="popover">{ popoverContent }</div> }
		</div>
	),
} ) );

// If component prop is unused but required for type checking:
// eslint-disable-next-line @typescript-eslint/no-unused-vars -- Mock is used by parent component
EllipsisMenuWrapper: ( { provider }: { provider: { id: string } } ) => (
	<div data-testid="ellipsis-menu">EllipsisMenu</div>
),
```

**Patterns**:

- Use `data-testid` to make components testable
- **Never use `any` types** - always define proper TypeScript interfaces
- Add inline documentation for ESLint disables explaining why
- Keep mock implementations simple but type-safe

### Test Organization

Group tests by concern:

```typescript
describe( 'ComponentName', () => {
	describe( 'Basic Rendering', () => { /* ... */ } );
	describe( 'Status Badge Rendering', () => { /* ... */ } );
	describe( 'Button Rendering', () => { /* ... */ } );
	describe( 'Props Handling', () => { /* ... */ } );
} );
```

### Test Naming

**CRITICAL**: Test names must accurately describe what the test actually verifies.

**Bad** (misleading):

```typescript
it( 'passes installingPlugin to child components', () => {
	// Only verifies component renders, doesn't check prop passing
	render( <Component installingPlugin="test" /> );
	expect( getByTestId( 'child' ) ).toBeInTheDocument();
} );
```

**Good** (honest):

```typescript
it( 'renders without error when installingPlugin prop is provided', () => {
	// Accurately describes what we're testing
	render( <Component installingPlugin="test" /> );
	expect( getByTestId( 'child' ) ).toBeInTheDocument();
} );
```

**Why this matters**:

- Prevents false confidence in test coverage
- Makes it clear when tests need enhancement
- Helps future developers understand actual coverage gaps
- To truly verify prop passing, you'd need mock inspection infrastructure

**Pattern**: Name tests for what they verify, not what you wish they verified.

### Testing State Variations

Use the mock helper to test different states:

```typescript
it( 'shows status for unsupported gateway', () => {
	const gateway = createMockGateway( {
		onboarding: {
			state: { supported: false, /* ... */ },
			messages: { not_supported: 'Not available' },
		},
	} );
	// Test logic
} );
```

### Common Test Scenarios

**For payment gateway components, test**:

1. Basic rendering (title, icon, description)
2. Status determination logic (all status types)
3. Conditional rendering (badges, buttons based on state)
4. WooPayments-specific behavior
5. Props handling and callbacks
6. **Edge cases and error conditions** (see below)

### Edge Case Testing (Critical for Defensive Coding)

Always include tests for defensive coding scenarios to prevent runtime errors:

**Missing optional fields:**

```typescript
it( 'handles missing gateway icon gracefully', () => {
	const gateway = createMockGateway( { icon: undefined } );
	const { container } = render( <Component gateway={ gateway } /> );
	// Verify component doesn't crash.
	expect( container.querySelector( '.icon' ) ).not.toBeInTheDocument();
} );
```

**Undefined nested objects:**

```typescript
it( 'handles undefined supports array', () => {
	const gateway = createMockGateway( { supports: undefined } );
	// Component should render without crashing.
	render( <Component gateway={ gateway } /> );
} );
```

**Invalid or conflicting state combinations:**

```typescript
it( 'handles conflicting state flags gracefully', () => {
	const gateway = createMockGateway( {
		state: {
			enabled: true,
			account_connected: false,
			needs_setup: true,
			// ... conflicting flags
		},
	} );
	// Component should prioritize and handle gracefully.
	render( <Component gateway={ gateway } /> );
} );
```

**Common edge cases to test:**

- Missing `icon`, `description`, `_suggestion_id`, `_incentive`
- `null` values in nested objects (e.g., `messages.not_supported`)
- Empty arrays (`supports: []`)
- `undefined` arrays or objects
- Conflicting boolean state flags
- Empty strings vs `null` vs `undefined`

**Why edge case testing matters:**

- Prevents crashes from unexpected API responses
- Validates defensive coding patterns
- Ensures graceful degradation
- Documents expected behavior under error conditions
- Catches regressions when refactoring null checks

### Code Quality Checklist

Before committing tests, verify:

- ✅ No `any` types - use proper TypeScript interfaces
- ✅ Import order: external dependencies before internal
- ✅ ESLint disable directives include inline documentation (with proper punctuation)
- ✅ **All inline comments end with proper punctuation (periods)**
- ✅ All URLs use HTTPS (never HTTP)
- ✅ Mock components have proper type definitions
- ✅ Test names accurately describe what they verify
- ✅ **Edge case tests included** (undefined/null values, missing fields, conflicting states)
- ✅ Tests pass: `pnpm run test:js -- [test-file]`
- ✅ No linting errors: `pnpm run lint:lang:js -- [test-file]`

## Notes

- Tests located in `test/` subdirectories alongside components
- Status badge types defined in `StatusBadgeProps` interface
- When adding new statuses: update type, CSS class, message, tests
- Status checks evaluated in priority order (check code for current priority)
