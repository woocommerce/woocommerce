# Claude Code Documentation for WooCommerce Admin Client

**Scope**: React/TypeScript development, Jest testing, Webpack builds
**Location**: `plugins/woocommerce/client/admin`

**See also:**

- `../../CLAUDE.md` - PHP tests and plugin-level documentation
- `client/settings-payments/CLAUDE.md` - Settings Payments module patterns

## Quick Reference Commands

```bash
# Testing
pnpm run test:js                             # Run all tests
pnpm run test:js -- status-badge.test.tsx    # Specific file

# Linting (ONLY specific files)
npx eslint --fix path/to/file.tsx            # Fix specific file
npx eslint path/to/file.tsx                  # Check specific file
pnpm run ts:check                            # Type checking
markdownlint --fix path/to/file.md           # Lint markdown files

# Building
pnpm run build                               # Production build
```

## When to Use This Documentation

Use this doc when you need to:

- Run or write Jest tests for React components
- Lint JavaScript/TypeScript/SCSS code
- Build or watch the admin client bundle
- Understand the admin client architecture
- Troubleshoot test or build failures

For module-specific patterns (like settings-payments), see the module's CLAUDE.md.

## Overview

The WooCommerce Admin client is a React-based application that provides the modern admin interface for WooCommerce.
It includes:

- Analytics dashboards and reports
- Onboarding flows
- Payment settings interface
- Activity panels
- Task lists
- Custom components and UI elements

**Technology Stack:**

- React 18.3.x
- TypeScript 5.7.x
- Jest for testing
- Webpack 5 for bundling
- WordPress packages (Components, Data, etc.)

## Running Tests

### JavaScript/Jest Tests

Run JavaScript tests using Jest and React Testing Library:

```bash
# Run all JavaScript tests
pnpm run test:js

# Run a specific test file
pnpm run test:js -- status-badge.test.tsx

# Run tests matching a pattern
pnpm run test:js -- --testNamePattern="StatusBadge"

# Run tests with coverage report
pnpm run test:js -- --coverage

# Update snapshots (if using snapshot testing)
pnpm run test:js -- -u

# Examples:
pnpm run test:js -- client/settings-payments/components/status-badge
pnpm run test:js -- --testPathPattern="complete-setup-button"
```

### Jest Configuration

- **Config file**: `client/jest.config.js`
- **Test framework**: Jest 29.5.x with React Testing Library
- **Test files**: Located in `test/` subdirectories next to components
    - Example: `client/settings-payments/components/status-badge/test/status-badge.test.tsx`

### Writing Tests

Tests follow the Jest + React Testing Library pattern:

```typescript
import { render } from '@testing-library/react';
import { MyComponent } from '../my-component';

describe('MyComponent', () => {
  it('renders correctly', () => {
    const { getByText } = render(<MyComponent title="Test" />);
    expect(getByText('Test')).toBeInTheDocument();
  });
});
```

## Linting

### Run All Linting

```bash
# Run all linting (JavaScript and CSS)
pnpm run lint

# Fix all auto-fixable linting issues
pnpm run lint:fix
```

### JavaScript/TypeScript Linting

### CRITICAL: Only lint/fix specific files you changed - NEVER the entire codebase

```bash
# ALWAYS run auto-fix on specific changed files only (use npx eslint directly)
npx eslint --fix client/settings-payments/components/status-badge/status-badge.tsx

# Check a specific file after fixing
npx eslint client/settings-payments/components/status-badge/status-badge.tsx

# Fix multiple specific files
npx eslint --fix file1.tsx file2.tsx file3.tsx

# ❌ NEVER run these commands (they lint the entire codebase):
pnpm run lint               # NO - lints everything
pnpm run lint:fix           # NO - lints everything
pnpm run lint:lang:js       # NO - lints entire ./client directory
pnpm run lint:fix:lang:js   # NO - lints entire ./client directory
```

**Correct workflow:**

1. Make your code changes
2. Run `npx eslint --fix path/to/file.tsx` for each changed file
3. Verify (optional): `npx eslint path/to/file.tsx`
4. Commit

**Note:** The pnpm lint scripts have hardcoded paths (`./client`) so they always lint the entire directory
even when you pass file arguments. Use `npx eslint` directly for per-file linting.

**Why this matters:**

- Prevents unrelated formatting changes in other files
- Keeps commits focused on your changes
- Avoids merge conflicts from mass formatting changes
- Respects existing code style in unchanged files

**JavaScript Linting Configuration:**

- **Tool**: ESLint 8.x
- **Config**: Uses `@woocommerce/eslint-plugin`
- **Files**: `./client/**/*.{js,ts,tsx}`
- **Cache**: `node_modules/.cache/eslint`
- **Note**: ESLint may show warnings from other files during scans; ignore them

### CSS/SCSS Linting

```bash
# Lint SCSS files
pnpm run lint:lang:css

# Fix SCSS linting issues
pnpm run lint:fix:lang:css
```

**CSS Linting Configuration:**

- **Tool**: Stylelint 14.x
- **Config**: Uses `@wordpress/stylelint-config`
- **Files**: `**/*.scss` (excludes `storybook/wordpress`)
- **Cache**: `node_modules/.cache/stylelint`

### TypeScript Type Checking

```bash
# Run TypeScript type checking
pnpm run ts:check
```

This runs `tsc --build` to check TypeScript types without emitting files.

### Markdown Linting

For detailed markdown linting instructions, see the "Markdown Linting"
section in `../../CLAUDE.md`.

## Building

### Production Build

```bash
# Build the project and all dependencies
pnpm run build

# Build only this project
pnpm run build:project

# Build the bundle only
pnpm run build:project:bundle

# Build feature config
pnpm run build:project:feature-config
```

**Build Configuration:**

- **Tool**: Webpack 5.x
- **Config**: `webpack.config.js`
- **Output**: `build/` directory
- **Source files**: `client/**/*.{js,jsx,ts,tsx,scss}`

### Development/Watch Mode

```bash
# Watch and rebuild on changes (all dependencies)
pnpm run watch:build

# Watch only this project
pnpm run watch:build:project
```

Watch mode automatically rebuilds when source files change.

## Development Workflow

### Recommended Workflow

1. **Start watch mode** to automatically rebuild on changes:

   ```bash
   pnpm run watch:build
   ```

2. **Run tests in watch mode** in a separate terminal:

   ```bash
   pnpm run test:js -- --watch
   ```

3. **Make your changes** to the code

4. **Check types** before committing:

   ```bash
   pnpm run ts:check
   ```

5. **Lint/fix only changed files**:

   ```bash
   npx eslint --fix path/to/changed-file.tsx
   ```

6. **Commit** only after tests pass and linting is clean

### Pre-commit Checks

The repository uses lint-staged for pre-commit checks:

**SCSS files:**

- Runs `pnpm lint:css-fix` on staged `.scss` files

**JS/TS files:**

- Runs `pnpm lint:js-pre-commit` on staged `.(t|j)s?(x)` files
- Runs `pnpm test-staged` to test affected files

## Architectural Patterns

### Data Layer / UI Separation (Monorepo Pattern)

WooCommerce uses a strict separation between data layer and UI layer:

**Data layer** (in `packages/js/data/`):

- TypeScript types and interfaces
- Data store selectors and actions
- Must be updated BEFORE UI changes

**UI layer** (in `client/admin/client/`):

- React components
- Depends on data layer types
- Update AFTER data layer

**Workflow**: When adding features, always update in this order:

1. Update types in `packages/js/data/src/[module]/types.ts`
2. Update test stubs in `packages/js/data/src/[module]/test/helpers/`
3. Update UI components in `client/[module]/components/`
4. Run tests and type checking

### Security Pattern: Minimal Props for Disabled Features

When rendering disabled/unsupported features, pass minimal props to prevent inadvertent action exposure:

- Use empty strings for URLs instead of actual endpoints
- Pass no-op functions (`() => {}`) instead of real callbacks
- Omit sensitive props (API keys, onboarding tokens, etc.)
- Explicitly set `disabled={true}`

**Rationale**: Defense in depth - even if the disabled state is bypassed, no sensitive actions can be triggered.

## Accessibility (WAI-ARIA)

All UI components must follow WAI-ARIA guidelines. This section captures only the
WooCommerce-specific guidance; for the general rules (semantic HTML first, ARIA
patterns for dialogs/forms/status, focus management, jest-axe) follow the
[WordPress Accessibility Handbook](https://make.wordpress.org/accessibility/handbook/)
and the [WAI-ARIA Authoring Practices](https://www.w3.org/WAI/ARIA/apg/).

**Test with React Testing Library's accessibility queries**, in priority order:
`getByRole` (interactive elements) → `getByLabelText` (form inputs) → `getByText`
→ `getByTitle`. Prefer these over `data-testid` so tests assert the accessible name.

### Common WooCommerce Admin Patterns

**WordPress Components:**

When using `@wordpress/components`, these already include proper ARIA:

- `<Button>` - Includes proper roles and keyboard support
- `<Modal>` - Includes focus trap and `aria-modal`
- `<Notice>` - Includes `role="alert"` for errors
- `<Popover>` - Includes proper labeling and focus management

```typescript
import { Button, Modal, Notice } from '@wordpress/components';

// These are already accessible
<Button variant="primary">Save Settings</Button>
<Modal title="Gateway Setup" onRequestClose={onClose}>...</Modal>
<Notice status="error">Failed to connect</Notice>
```

### Common Violations to Avoid

| Issue | Wrong | Correct |
| ------- | ------- | --------- |
| **Missing button label** | `<button><Icon /></button>` | `<button aria-label="Save"><Icon /></button>` |
| **Non-semantic click** | `<div onClick={...}>Click</div>` | `<button onClick={...}>Click</button>` |
| **Missing form label** | `<input placeholder="Name" />` | `<label>Name<input /></label>` |
| **Status without ARIA** | `<span>Active</span>` | `<span role="status">Active</span>` |
| **Disabled without ARIA** | `<button disabled>...</button>` | `<button disabled aria-disabled="true">...</button>` |

## Code Structure

### Directory Organization

```text
client/admin/
├── client/                    # Source code
│   ├── settings-payments/    # Payment settings interface
│   │   ├── components/       # React components
│   │   │   ├── status-badge/
│   │   │   ├── buttons/
│   │   │   └── ...
│   │   ├── onboarding/       # Onboarding flows
│   │   ├── test/             # Module-level tests
│   │   └── utils/            # Utilities
│   ├── analytics/            # Analytics features
│   ├── dashboard/            # Dashboard components
│   ├── task-lists/           # Task list features
│   └── ...
├── docs/                      # Documentation
│   ├── README.md
│   ├── data.md
│   ├── layout.md
│   ├── examples/             # Extension examples
│   └── features/             # Feature documentation
├── build/                     # Build output (generated)
├── client/jest.config.js     # Jest configuration
├── webpack.config.js         # Webpack configuration
├── tsconfig.json             # TypeScript configuration
└── package.json              # Package configuration
```

### Key Files

- **`webpack.config.js`**: Webpack build configuration
- **`tsconfig.json`**: TypeScript configuration
- **`client/jest.config.js`**: Jest test configuration
- **`.eslintrc.js`** or **`eslint.config.js`**: ESLint configuration
- **`babel.config.js`**: Babel transpilation configuration

## Testing Patterns

### Component Tests

Component tests should be placed in a `test/` directory next to the component:

```text
components/
├── status-badge/
│   ├── status-badge.tsx
│   ├── status-badge.scss
│   ├── index.ts
│   └── test/
│       └── status-badge.test.tsx
```

### Common Test Utilities

- `@testing-library/react` - For rendering and testing React components
- `@testing-library/user-event` - For simulating user interactions
- `@testing-library/jest-dom` - For additional Jest matchers
- `@wordpress/jest-preset-default` - WordPress-specific Jest preset

### Test Coverage

Generate coverage reports:

```bash
pnpm run test:js -- --coverage
```

Coverage reports help identify untested code paths.

## Troubleshooting

### Common Issues

**Tests failing:**

- Ensure dependencies are installed: `pnpm install`
- Clear Jest cache: `pnpm run test:js -- --clearCache`
- Check TypeScript types: `pnpm run ts:check`

**Build failures:**

- Clean build directory: `rm -rf build/`
- Rebuild dependencies: `pnpm install`
- Check webpack config for errors

**Linting errors:**

- Auto-fix what's possible: `pnpm run lint:fix`
- Review remaining errors manually
- Check `.eslintrc.js` for rule configuration

**TypeScript errors:**

- Run type checking: `pnpm run ts:check`
- Ensure all dependencies have types installed
- Check `tsconfig.json` configuration

**Module not found errors:**

- Verify import paths are correct
- Check if package is in `dependencies` or `devDependencies`
- Run `pnpm install` to ensure all packages are installed

### Performance Tips

**Speed up tests:**

- Run specific tests instead of the entire suite
- Use watch mode to only run affected tests
- Consider using `--maxWorkers=50%` to limit CPU usage

**Speed up builds:**

- Use watch mode instead of rebuilding manually
- Ensure webpack cache is enabled (default)
- Consider using webpack-bundle-analyzer to identify large bundles

## Additional Resources

### Documentation

- **Main docs**: `docs/README.md`
- **Data layer**: `docs/data.md` - Information about data stores and state management
- **Layout**: `docs/layout.md` - Layout components and structure
- **Page controller**: `docs/page-controller.md` - Page routing and navigation
- **Examples**: `docs/examples/` - Extension examples and guides
- **Features**: `docs/features/` - Feature documentation

### Extension Development

The `docs/examples/extensions/` directory contains examples for:

- Adding custom reports
- Creating inbox notifications
- Adding dashboard sections
- Creating custom tasks
- Modifying tables and columns

To build examples:

```bash
pnpm run example
```

### Changelog Management

Generate changelog entries:

```bash
pnpm run changelog
```

This uses Composer's changelogger to manage changelog entries.

## Environment Requirements

- **Node.js**: ^24.15.0 (specified in `engines.node`)
- **pnpm**: Latest stable version
- **PHP**: Required for feature config generation

## CI/CD

The package includes CI configuration in `config.ci`:

**Linting:**

- Command: `lint`
- Triggers on changes to: `client/**/*.{js,ts,tsx,scss}`

**Tests:**

- Command: `test:js`
- Triggers on changes to:
    - `jest.config.js`
    - `webpack.config.js`
    - `babel.config.js`
    - `tsconfig.json`
    - `client/**/*.{js,jsx,ts,tsx,scss,json}`

## Notes for Development

- Always run tests after making changes
- Use TypeScript for new code
- Follow existing component patterns
- Add tests for new components and features
- Keep dependencies up to date but test thoroughly
- Use workspace dependencies (`workspace:*`) for internal packages
- Respect the monorepo structure - this package depends on other WooCommerce packages
