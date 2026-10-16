# WooCommerce themes

Each theme lives in `themes/<slug>/` as an independent workspace package, with its own version, changelog, and release process.

## Available themes

- [Purple](purple/README.md)

## Development

Shared validation, ZIP packaging, and local environment commands are provided by `@woocommerce/theme-cli`. Run `pnpm --filter=@woocommerce/theme-cli validate --all` from the repository root to lint and validate every theme. Themes can register validation in the monorepo's existing package CI to check affected themes on pull requests. See the [theme CLI guide](../tools/theme-cli/README.md) for setup, validation, and editor configuration.
