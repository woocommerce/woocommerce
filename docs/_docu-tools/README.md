# Website

This website is built using [Docusaurus](https://docusaurus.io/), a modern static website generator.

### Installation

```
$ npm install 
```

### Local Development

```
$ npm run start
```

This command starts a local development server and opens up a browser window. Most changes are reflected live without having to restart the server.

### Build

```
$ npm run build
```

This command generates static content into the `build` directory and can be served using any static contents hosting service.

### Deployment

The contents in the `woocommerce/docs` folder are pulled via a GitHub Actions cron job defined [here](https://github.com/woocommerce/woo-docs-build/blob/trunk/.github/workflows/deploy-docs.yml). 

They are then built for production and a PR is created against https://github.com/wpcomvip/woocommerce-woo-docs-multi-com in that same GitHub Action. 

