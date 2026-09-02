# Website

This website is built using [Docusaurus](https://docusaurus.io/), a modern static website generator.

## Installation

```bash
pnpm install
```

## Local Development

```bash
pnpm start
```

This command starts a local development server and opens up a browser window. Most changes are reflected live without having to restart the server.

## Build

```bash
pnpm build
```

This command generates static content into the `build` directory and can be served using any static contents hosting service.

## Deployment

The contents in the `woocommerce/docs` folder are pulled via a nightly GitHub Actions cron job in the [woocommerce-woo-docs-multi-com repository](https://github.com/wpcomvip/woocommerce-woo-docs-multi-com), built for production, and a PR is created against the main branch of the same repository. The PR is then merged and the changes are deployed to the live site.

Changes to the developer docs will typically show up [on the live site](https://developer.woocommerce.com/docs/) within 24 hours of being merged.

