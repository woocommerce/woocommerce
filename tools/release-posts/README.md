# Release Post Generator CLI tool

This is a cli tool designed to generate draft release posts for WooCommerce.
Posts generated via the tool will be draft posted to <https://developer.woocommerce.com>.

You can also generate an HTML representation of the post if you
don't have access to a WordPress.com auth token.

## Setup

1. Make sure `pnpm i` has been run in the monorepo.
2. Make sure you have added a `.env` file with the env variables set. WCCOM_TOKEN is optional if you're using `--outputOnly`, but
   the `GITHUB_ACCESS_TOKEN` is required. If you need help generating a token see [the docs](https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/creating-a-personal-access-token). To silence all CLI output, set `LOGGER_LEVEL` to `"silent"`.
3. Note that the env file should live at the same path that you're running the command from.
4. Run the tool via the npm script, e.g. `pnpm release-post release "6.8.0" --outputOnly`
5. For more help on individual options, run the help `pnpm release-post <command> --help`. e.g. `pnpm release-post rc --help`

## Publishing Draft Posts

This tool will publish draft posts to `https://developer.woocommerce.com` for you if you omit the `--outputOnly` flag. There is some minimal first time setup for this though:

1. Create an app on WordPress.com [here](https://developer.wordpress.com/apps/).
2. Recommended settings:
   - Name can be anything
   - Description can be left blank
   - Website URL just put `http://localhost`
   - Redirect URLs, by default you should add: `http://localhost:3000/oauth`
   - JavaScript Origins put `http://localhost`
   - Type - choose "Web"
3. Once your app is created you can go back to the
   app list and click "manage app".
4. Take note of the `client secret` and the `client id`.
5. In your `.env` file add the client secret to the `WPCOM_OAUTH_CLIENT_SECRET` variable and the client id to the `WPCOM_OAUTH_CLIENT_ID` variable.

## Generating Just a Contributors List

If you don't have a final release yet you can generate an HTML contributors list that you can copy
paste into a blank post.

To do that simply run `pnpm release-post contributors "<currentVersion>" "<previousVersion>"`

## Advanced

If you can't run anything on your localhost port 3000 you may want to override the redirect uri for oauth.

Steps:

1. Add your preferred redirect URI to the `WPCOM_OAUTH_REDIRECT_URI` variable in `.env`. e.g. `http://localhost:4321/oauth`
2. When creating your app on [WordPress.com](https://developer.wordpress.com/apps/) make sure the redirect URL you set matches the one set in `.env`
