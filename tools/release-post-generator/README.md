## Release Post Generator CLI tool

This is a cli tool designed to generate draft release posts for WooCommerce.
Posts generated via the tool will be draft posted to https://developer.woocommerce.com.

You can also generate an HTML representation of the post if you
don't have access to a wc.com auth token.

### Setup

1. Make sure `pnpm i` has been run in the monorepo.
2. Make sure you have added a `.env` file with the env variables set. WCCOM_TOKEN is optional if you're using `--outputOnly`, but
the `GITHUB_ACCESS_TOKEN` is required. If you need help generating a token see [the docs](https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/creating-a-personal-access-token). To silence all CLI output, set `LOGGER_LEVEL` to `"silent"`.
3. Run the tool from this directory, e.g. `ts-node ./index.ts release "6.8.0" --outputOnly`
4. For more help on individual options, run the help `ts-node ./index.ts release --help`



