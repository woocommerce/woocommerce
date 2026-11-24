# Purple development and releases

Purple is the private `@woocommerce/theme-purple` workspace package. Its installable artifact is a WordPress theme, and its releases are independent of other themes and WooCommerce Core.

## Development

From the repository root, run `pnpm install`, `composer install --working-dir=tools/theme-cli`, and `composer install --working-dir=themes/purple`.

```bash
pnpm --filter=@woocommerce/theme-purple validate
pnpm --filter=@woocommerce/theme-purple changelog add
pnpm --filter=@woocommerce/theme-purple changelog validate
```

### Playground environment

The shared theme CLI starts WordPress with the experimental Playground runtime by default:

```bash
pnpm --filter=@woocommerce/theme-cli wp-env start purple
```

## Releases

### Update versions and changelog

Create a pull request including these changes (see #71001 and #71051 for an example):

* Move changelog entries to `readme.txt`.
* Update WC and WP compatibility versions, we follow a L-1 compatibility policy.
* Update Purple versions to the version you are releasing.

### Build the ZIP

After those changes are merged into `trunk`, check out `trunk` and make sure you don't have staged or uncommitted changes. Then, from the repository root, run:

```bash
pnpm --filter=@woocommerce/theme-purple validate
pnpm --filter=@woocommerce/theme-purple build
```

A ZIP will be generated in `themes/purple/artifacts/purple.zip`.

Install that ZIP on a test site with the [Theme Check](https://wordpress.org/plugins/theme-check/) plugin active, go to Appearance > Theme Check and verify all checks pass. Smoke test a few screens.

### Tag and push

Create a tag for the Purple release and push it:

```bash
git tag -a purple-1.1.0 -m "purple-1.1.0"
git push origin purple-1.1.0
```

### GitHub release

Create a [GitHub release](https://github.com/woocommerce/woocommerce/releases) for the tag and upload `purple.zip` to it.

### WordPress.org release

Upload the same ZIP to [WordPress.org](https://wordpress.org/themes/upload/).

After a few minutes, verify the new version is available in the [Purple page in WordPress.org](https://wordpress.org/themes/purple/).

### Communication

Publish a new post with the changelog and some highlights to the [WooCommerce Product Updates](https://woocommerce.com/product-updates/) page.
