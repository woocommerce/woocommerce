# Version Bump

## Description

`version-bump` is a CLI tool to bump versions in plugins found in the Monorepo.

## Usage

Bump WooCommerce to version 7.1.0:

```
pnpm --filter version-bump run version bump woocommerce -v 7.1.0
```

**Arguments**:
plugin - Monorepo plugin

**Options**:
-v, --version <string> Version to bump to
-h, --help display help for command

### Prereleases

Prerelease versions such as `7.3.0-dev` and `7.5.0-beta.1` are acceptable.

When updating with a `-dev` prerelease suffix, the tool will not update the stable tag but will update the readme changelog to prepare for the next release cycle.
