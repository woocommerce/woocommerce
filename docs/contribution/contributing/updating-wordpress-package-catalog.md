# Updating the WordPress package catalog

WooCommerce keeps a curated `wp-min` catalog in `pnpm-workspace.yaml` for `@wordpress/*` packages that match the minimum supported WordPress version.

Use `pnpm view` to read the npm dist tag for the WordPress version being targeted. For example:

```bash
pnpm view @wordpress/components@wp-6.9 version
```

To print lookup commands for every `@wordpress/*` package currently in the catalog, run:

```bash
WP_TAG=wp-6.9
sed -n "/wp-min:/,/^packages:/p" pnpm-workspace.yaml \
	| rg -o "'@wordpress/[^']+'" \
	| tr -d "'" \
	| while read package_name; do
		printf 'pnpm view %s@%s version\n' "$package_name" "$WP_TAG"
	done
```

To query the versions directly, run:

```bash
WP_TAG=wp-6.9
sed -n "/wp-min:/,/^packages:/p" pnpm-workspace.yaml \
	| rg -o "'@wordpress/[^']+'" \
	| tr -d "'" \
	| while read package_name; do
		printf '%s: ' "$package_name"
		pnpm view "$package_name@$WP_TAG" version
	done
```

The `@types/wordpress__*` packages do not publish WordPress dist tags. When they still exist in the catalog, check them separately with:

```bash
pnpm view @types/wordpress__block-editor version
```

Keep `@wordpress/private-apis` as a caret range in `pnpm-workspace.yaml`. That package carries the list of private packages, and allowing compatible updates avoids duplicate private API package lists in the resolved dependency tree.
