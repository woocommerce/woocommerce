# Admin screens

Experimental admin pages built with wp-build, DataForm and core-data. Each folder in `routes/` is a wp-build route.

```sh
pnpm --filter=@woocommerce/plugin-woocommerce build:project:admin-screens
pnpm --filter=@woocommerce/plugin-woocommerce watch:build:project:admin-screens
```

The build writes to `plugins/woocommerce/assets/client/routes`.
