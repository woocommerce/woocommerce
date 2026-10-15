# Code Quality Commands

## Table of Contents

- [Overview](#overview)
- [PHP Linting](#php-linting)
- [JavaScript Linting](#javascript-linting)
- [Markdown Linting](#markdown-linting)
- [Dependency Changes](#dependency-changes)
- [Important Linting Guidelines](#important-linting-guidelines)
- [Example Workflow](#example-workflow)
- [Understanding Linting Output](#understanding-linting-output)
- [Pre-Commit Checklist](#pre-commit-checklist)
- [Integration with Development Cycle](#integration-with-development-cycle)
- [Additional Linting Tools](#additional-linting-tools)
- [Troubleshooting](#troubleshooting)
- [Notes](#notes)

## Overview

When making changes to the WooCommerce codebase, run these commands to ensure code quality and adherence to coding standards.

For detailed PHP linting patterns and common issues, see [php-linting-patterns.md](php-linting-patterns.md).

For markdown linting rules and workflow, see [markdown-linting.md](markdown-linting.md).

## PHP Linting

### Check for PHP Linting Issues

```bash
pnpm run lint:changes:branch:php
```

Checks changed files for WordPress Coding Standards violations (read-only).

### Fix PHP Code Style Issues

```bash
# Automatically fix PHP code style issues
pnpm run lint:php:fix
```

This command:

- Automatically fixes code style violations where possible
- Applies WordPress Coding Standards formatting
- Modifies files in place
- Should be run before committing

### Advanced PHP Linting

If you need more control, you can use phpcs and phpcbf directly:

```bash
# Check specific file or directory
vendor/bin/phpcs path/to/file.php

# Check with specific standard
vendor/bin/phpcs --standard=WordPress path/to/file.php

# Fix specific file
vendor/bin/phpcbf path/to/file.php

# Show all violations (including warnings)
vendor/bin/phpcs -s path/to/file.php
```

## JavaScript Linting

### Check for JS Linting Issues

```bash
# Run JS linting on changes in your branch
pnpm --filter=@woocommerce/plugin-woocommerce lint:changes:branch:js
```

This command:

- Checks only JavaScript/TypeScript files changed in your current branch
- Identifies code style and potential issues
- Does not modify files

**Important:** CI's "Lint - @woocommerce/plugin-woocommerce" job runs `lint:changes:branch`. Its JS half (`bin/eslint-branch.sh`) passes every changed `.js`, `.jsx`, `.ts` and `.tsx` file under `plugins/woocommerce` to ESLint, which lints each file with its nearest `eslint.config.mjs`. A `client/legacy` file gets `client/legacy/eslint.config.mjs` (which sets `max-len` to 140), and a `client/blocks` file gets the blocks config, Prettier included. A sub-package's own lint can pass while this job fails, because it lints fewer files: `pnpm --filter=@woocommerce/classic-assets lint:lang:js` only covers `js/admin/*.js` and `js/frontend/*.js`, and skips subfolders such as `js/frontend/test/`. Before pushing, run the job's command from anywhere in the repository:

```bash
git fetch origin trunk
pnpm --filter=@woocommerce/plugin-woocommerce lint:changes:branch:js
```

The script finds your changes from the merge base with `origin/trunk`, so fetch first: a stale ref makes it also lint files trunk changed since. For a PR that targets another branch, CI compares against that branch instead, so fetch it and pass it, for example `git fetch origin release/X.Y` and then `pnpm --filter=@woocommerce/plugin-woocommerce lint:changes:branch:js origin/release/X.Y`. The script skips untracked files, so `git add` new files first. For SCSS changes in `client/legacy`, also run `pnpm --filter=@woocommerce/classic-assets lint`.

**Important:** CI also runs a separate "Lint - @woocommerce/block-library" job over the whole blocks package, including stylelint for SCSS. For blocks changes, also run:

```bash
# Check blocks JS/TS (ESLint with Prettier)
pnpm --filter=@woocommerce/block-library lint:js

# Check blocks SCSS, if you changed any
pnpm --filter=@woocommerce/block-library lint:css

# Auto-fix blocks JS/TS issues
pnpm --filter=@woocommerce/block-library lint:js-fix
```

For detailed JavaScript/TypeScript linting configuration and patterns, see `client/admin/CLAUDE.md`.

## Markdown Linting

Always lint markdown files after making changes. See [markdown-linting.md](markdown-linting.md) for complete details.

**Quick commands:**

```bash
# Auto-fix most issues
markdownlint --fix path/to/file.md

# Check for remaining errors
markdownlint path/to/file.md
```

## Dependency Changes

Any `package.json` change makes pnpm re-resolve that workspace package's dependency tree, which includes tooling entries shared with other packages, and it rewrites those shared entries to match whichever package it resolved last: their peer suffixes, and sometimes the chosen peer version. Adding a dependency-free `jquery` to `client/legacy` rewrote over 200 unrelated `pnpm-lock.yaml` lines, and repeated runs gave the same rewrite through an npm proxy and through the public registry. The rewrites cannot be settled: a lockfile re-resolved package by package still churns on the next dependency change, so do not try to fix them in your PR. Reviewers will ask for a lockfile diff that holds only your entries.

Every command below works from any directory in the repository. For a PR that targets another branch, use that branch in place of `trunk` throughout.

1. Add the dependency, then check the lockfile against your branch's merge base with trunk:

   ```bash
   git fetch origin trunk
   pnpm --filter=@woocommerce/classic-assets add -D jquery4@npm:jquery@4.0.0
   git diff --numstat "$(git merge-base origin/trunk HEAD)" -- :/pnpm-lock.yaml
   ```

   If the second column (deleted lines) is `0` and every added line is one of the entries step 3 lists, skip to step 4.

2. Otherwise, save pnpm's lockfile, then restore the merge-base one. Run both lines together, in this order: the lockfile pnpm wrote is not committed, and the restore overwrites it.

   ```bash
   cp "$(git rev-parse --show-toplevel)/pnpm-lock.yaml" "${TMPDIR:-/tmp}/pnpm-lock.pnpm-written.yaml"
   git checkout "$(git merge-base origin/trunk HEAD)" -- :/pnpm-lock.yaml
   ```

3. Copy from the saved file into the restored one only the entries your dependency reaches, verbatim and at the position pnpm wrote them (keys are sorted, with a blank line between entries):

   - Under `importers:`, in your package's path and dependency field, the entry for each dependency you added, with its `specifier` and `version` lines.
   - The `snapshots:` entry that version points to, and the `packages:` entry with the same key minus the parenthesized peer suffix. The key is `<name>@<version>`, or the `version` value itself for an alias such as `jquery@4.0.0`. Skip any key the restored file already has.
   - The same for every line under that snapshot's `dependencies:` and `optionalDependencies:`, recursively, stopping at keys the restored file already has.
   - Under `catalogs:`, the entry, if your specifier is `catalog:…`.

   Copy nothing else. The saved file also holds new keys for re-resolved peer variants of unrelated packages. Those are the churn, and no check flags them as unused once copied. If the saved file also changes an existing entry that names the package you added, an insert-only edit cannot be correct: stop and ask a human. To compare the two files:

   ```bash
   git diff --no-index "$(git rev-parse --show-toplevel)/pnpm-lock.yaml" "${TMPDIR:-/tmp}/pnpm-lock.pnpm-written.yaml"
   ```

4. Validate:

   ```bash
   pnpm -C "$(git rev-parse --show-toplevel)" install --frozen-lockfile
   ```

   A missing `packages:` or `snapshots:` entry fails with `ERR_PNPM_LOCKFILE_MISSING_DEPENDENCY`, and an importer entry that disagrees with its `package.json` fails with "specifiers in the lockfile don't match". Extra entries pass, which is why step 3 copies only what your dependency reaches.

5. Re-run the `git diff --numstat` check from step 1. The deleted-lines column must be `0`. Then delete `${TMPDIR:-/tmp}/pnpm-lock.pnpm-written.yaml`. For scale: `jquery` came to 8 added lines, `react-select` to 26 (its dependencies were already in the lockfile), and `yup` to 34 (four new transitive packages).

A lockfile change makes CI run `syncpack lint` ("Validate dependencies version"). pnpm does not resolve versions published less than 24 hours ago (`minimumReleaseAge` in `pnpm-workspace.yaml`).

If a later trunk merge conflicts in `pnpm-lock.yaml`, take trunk's version (`git checkout origin/trunk -- :/pnpm-lock.yaml`), run `pnpm install`, and continue from the check in step 1.

Do not run `pnpm dedupe` or delete the lockfile to "clean it up": both rewrite resolutions across the workspace, which is a dependency upgrade, not a cleanup.

## Important Linting Guidelines

### Only Fix Code in Your Branch

**Important:** Only fix linting errors for code that has been added or modified in the branch you are working on.

Do not fix linting errors in unrelated code unless specifically asked to do so.

**Why?**

- Keeps pull requests focused on the actual changes
- Avoids merge conflicts with other branches
- Makes code review easier
- Maintains clear git history

### Example Workflow

```bash
# 1. Make your code changes
# ... edit files ...

# 2. Check what you've changed
git status
git diff

# 3. Stage new files, then lint PHP and JS changes (what CI's Lint job runs)
git add -N path/to/new-file.js
pnpm --filter=@woocommerce/plugin-woocommerce lint:changes:branch

# 4. Fix PHP issues automatically (fix JS issues by hand)
pnpm --filter=@woocommerce/plugin-woocommerce lint:php:fix

# 5. Review the fixes
git diff

# 6. Commit your changes
git add .
git commit -m "Your commit message"
```

## Understanding Linting Output

### PHP CodeSniffer Output

```text
FILE: /path/to/file.php
----------------------------------------------------------------------
FOUND 2 ERRORS AFFECTING 2 LINES
----------------------------------------------------------------------
 12 | ERROR | [x] Expected 1 space after opening parenthesis;
    |       |     0 found
 25 | ERROR | [ ] Variable "$orderID" is not in valid snake_case
    |       |     format
----------------------------------------------------------------------
```

**Legend:**

- `[x]` - Can be fixed automatically with phpcbf/lint:php:fix
- `[ ]` - Requires manual fixing

### Common PHP Issues

1. **Spacing issues** - Usually auto-fixable

   ```php
   // Wrong
   if($condition){

   // Right
   if ( $condition ) {
   ```

2. **Naming conventions** - Requires manual fix

   ```php
   // Wrong
   $orderID

   // Right
   $order_id
   ```

3. **Yoda conditions** - Requires manual fix

   ```php
   // Wrong
   if ( $value === 'active' )

   // Right
   if ( 'active' === $value )
   ```

## Pre-Commit Checklist

Before committing your changes:

- [ ] `git add` any new files, then run `pnpm --filter=@woocommerce/plugin-woocommerce lint:changes:branch` (PHP and JS; this is what CI's Lint job runs, and it skips untracked files). For a PR that does not target trunk, fetch its base branch and append it, for example `origin/release/X.Y`
- [ ] Run `pnpm --filter=@woocommerce/plugin-woocommerce lint:php:fix` if PHP issues are found; there is no branch fixer for JS, so fix JS issues by hand
- [ ] If you changed a `package.json`, run `git diff --numstat "$(git merge-base origin/trunk HEAD)" -- :/pnpm-lock.yaml` and confirm it deletes nothing and adds only your entries (see [Dependency Changes](#dependency-changes))
- [ ] Run `pnpm --filter=@woocommerce/block-library lint:js-fix` if you modified blocks JS/TS files
- [ ] Review all automatic fixes with `git diff`
- [ ] Address any remaining issues that can't be auto-fixed
- [ ] Run tests to ensure fixes didn't break functionality

## Integration with Development Cycle

Code quality checks fit into the overall development workflow:

1. Make code changes
2. Run relevant tests (see running-tests.md)
3. **Run linting checks** ← You are here
4. **Fix code quality issues** ← You are here
5. Commit changes only after tests pass and linting is clean

## Additional Linting Tools

### Running Other pnpm Scripts

WooCommerce may have additional linting scripts. Check available scripts:

```bash
# See all available scripts
pnpm run

# Common additional scripts may include:
pnpm run lint           # Lint all files
pnpm run lint:fix       # Fix all auto-fixable issues
pnpm run lint:php       # PHP linting only
pnpm run lint:js        # JavaScript linting only
```

## Troubleshooting

### Linting Command Not Found

**Problem:** Command fails with "command not found"

**Solution:** Install dependencies:

```bash
pnpm install
```

### Too Many Issues Reported

**Problem:** Linting reports issues in files you didn't change

**Solution:** Make sure you're using the branch-specific commands:

```bash
# Good - only checks your changes
pnpm run lint:changes:branch:php

# Avoid - checks entire codebase
pnpm run lint:php
```

### Conflicts After Auto-Fix

**Problem:** Git conflicts after running lint:php:fix

**Solution:**

1. Review the automatic fixes: `git diff`
2. If fixes are incorrect, revert: `git checkout -- path/to/file.php`
3. Address the issues manually instead

## Notes

- Code quality tools help maintain consistency across the codebase
- Automatic fixes save time but should always be reviewed
- Some issues require manual intervention and understanding of the context
- Linting is required before committing to ensure code quality standards
