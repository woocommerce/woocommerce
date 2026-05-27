# Updating the WordPress package catalog

WooCommerce uses the `wp-min` catalog in `pnpm-workspace.yaml` for `@wordpress/*` packages that should match the package versions bundled with the minimum supported WordPress release.

## Find the package versions

WordPress publishes package dist tags for each release. Query the tag for the WordPress version being adopted:

```bash
pnpm npm view @wordpress/components dist-tags
```

Use the release tag values to update every package listed in the `wp-min` catalog. When adding a new `@wordpress/*` package to the catalog, also replace package-specific version pins with `catalog:wp-min` where the package should follow the minimum supported WordPress release.

## Refresh dependencies

After updating `pnpm-workspace.yaml` and any package manifests, refresh the lockfile:

```bash
pnpm install
```

Then check that dependency versions remain aligned:

```bash
pnpm syncpack:list
```

If a package must stay on a different version, document the reason in `.syncpackrc` with a targeted rule instead of relying on an unexplained package-level version.

## Validate the update

Run the focused checks for the packages touched by the catalog update. At minimum, confirm the changelog state before opening a PR:

```bash
php tools/monorepo/check-changelogger-use.php trunk HEAD
```
