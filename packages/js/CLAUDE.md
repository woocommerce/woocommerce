# Claude Code Documentation for WooCommerce JS Packages

**Location**: `packages/js/`

## Changelog Quick Reference

**Add entry for EVERY functional change to a package.**

```bash
# Quick add (interactive)
cd packages/js/[package-name]
pnpm changelog add

# Manual creation
# Create: packages/js/[package-name]/changelog/[type-brief-description]
# Format:
Significance: <patch|minor|major>
Type: <type>

<One-line description of the change>
```

**Significance (Semver):**

- `patch` → Bug fixes, no API changes (0.0.X)
- `minor` → New features, non-breaking (0.X.0)
- `major` → Breaking changes (X.0.0)

**Type:**

- `fix` → Fixes a bug
- `add` → Adds functionality
- `update` → Update existing functionality
- `dev` → Development/tooling task
- `tweak` → Minor adjustment
- `performance` → Performance improvement
- `enhancement` → Improve existing functionality

## Common Patterns & Decision Tree

**Type & Significance Quick Lookup:**

| Change Type | Type | Significance |
|------------|------|--------------|
| Bug fix | `fix` | `patch` |
| New feature/selector/action | `add` | `minor` |
| New TS type/interface property | `add` | `minor` |
| Update existing functionality (non-breaking) | `update` | `minor` |
| Breaking API change | `update` | `major` |
| Test/build/tooling | `dev` | `patch` |
| Performance improvement | `performance` | `minor` |

**Examples:**

```text
# Bug fix
Significance: patch
Type: fix

Fix race condition in payment settings data loader
```

```text
# New feature
Significance: minor
Type: add

Add `onboardingSupported` property to PaymentGateway interface
```

```text
# Breaking change
Significance: major
Type: update

Remove deprecated `getPaymentMethods` selector
```

```text
# Dev/tooling
Significance: patch
Type: dev

Update test stubs to include new gateway properties
```

## Important: @woocommerce/data Package

**Central data layer** - Always add changelog when making changes. Update order:

1. Types in `src/[module]/types.ts`
2. Test stubs in `src/[module]/test/helpers/`
3. Implementation
4. Add changelog entry

## Troubleshooting

**`changelogger: command not found`** → `composer install` in package directory

**No composer.json?** → Create changelog file manually in `changelog/` directory

**Uncertain about significance?** → Default to `minor` for features, `patch` for fixes

**CI failing on changelog?** → Run `pnpm changelog validate` before pushing

## Notes

- Changelog files committed WITH code changes (same commit/PR)
- One file per logical change
- File location: `packages/js/[package]/changelog/[entry-name]`
- Tool: `automattic/jetpack-changelogger` (configured in `composer.json`)
