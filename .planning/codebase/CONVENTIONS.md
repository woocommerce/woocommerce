# Coding Conventions

**Analysis Date:** 2026-02-02

## Naming Patterns

**Files:**

-   kebab-case: `settings-email-listing-status.tsx`
-   Test files with `.test.` suffix: `payment-recommendations.test.tsx`
-   Jest specs with `.spec.` suffix: `order-status-filter.spec.js`

**Functions:**

-   camelCase: `useTransactionalEmails`, `handleClick`
-   Hooks prefixed with "use": `useEntityRecords`, `useSelect`
-   Event handlers prefixed with "handle": `handleSubmit`, `handleCancel`

**Variables:**

-   camelCase: `emailStatuses`, `isLoading`
-   Constants in UPPER_SNAKE_CASE: `EMAIL_STATUSES`, `SETTINGS_SLOT_FILL_CONSTANT`
-   Component props use camelCase: `renderContent`, `slowThreshold`

**Types:**

-   PascalCase: `EmailType`, `EmailStatus`
-   Interface names without "I" prefix: `View`, `Post`
-   Generic types use single letters: `T`, `K`

## Code Style

**Formatting:**

-   WordPress Prettier config: `@wordpress/prettier-config`
-   Tabs for indentation (WordPress standard)
-   Single quotes for strings in JS/TS

**Linting:**

-   ESLint with `@wordpress/eslint-plugin`
-   Babel parser for ES6+ features
-   Stylelint with `@wordpress/stylelint-config`

## Import Organization

**Order:**

1. External dependencies (WordPress packages, third-party)
2. Internal dependencies (relative imports)
3. Type imports (TypeScript)

**Path Aliases:**

-   `~/` for admin client root: `~/utils/admin-settings`
-   `@woocommerce/` for workspace packages

## Error Handling

**Patterns:**

-   Try-catch blocks for async operations
-   Error boundaries for React components
-   `createNoticesFromResponse` for API error handling
-   Return early pattern for guard clauses

## Logging

**Framework:** console

**Patterns:**

-   `console.log` for development (limited by eslint rule: warn level)
-   WordPress notices for user-facing messages
-   Error tracking via `recordEvent` for analytics

## Comments

**When to Comment:**

-   Complex business logic
-   Non-obvious workarounds with TODO/FIXME
-   TypeScript type assertions with explanations
-   File headers with @package documentation

**JSDoc/TSDoc:**

-   Used for exported functions and components
-   Type annotations in JSDoc for legacy JS files
-   `@wordpress/*` packages have built-in types

## Function Design

**Size:** Keep functions focused and under 50 lines

**Parameters:**

-   Prefer object parameters for 3+ arguments
-   Use default parameters where sensible
-   Destructure props in function signature

**Return Values:**

-   Explicit return types for TypeScript functions
-   Early returns for error conditions
-   Consistent return types (avoid mixed null/undefined)

## Module Design

**Exports:**

-   Named exports preferred over default
-   Index files for public API: `index.ts`
-   Separate type exports in TypeScript

**Barrel Files:**

-   Used for component directories
-   Re-export main component and types

---

_Convention analysis: 2026-02-02_
