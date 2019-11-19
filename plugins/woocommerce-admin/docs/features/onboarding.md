# WooCommerce Onboarding

The onboarding feature is a reimagined new user setup experience for WooCommerce core. It contains two sections aimed at getting merchants started with their stores. The merchant begins with the "profile wizard", which helps with initial steps like setting a store address, making extension recommendations, and connecting to Jetpack for additional features. Once the profile wizard is complete, the merchant can purchase & install optional extensions via WooCommerce.com, before continuing to the "task list". From the task list, merchants are walked through a variety of items to help them start selling, such as adding their first product and setting up payment methods.

* Master Thread: p6q8Tx-R1-p2
* Designs: 2317a-pb

## Enabling Onboarding

If you run the development version of WooCommerce Admin from GitHub directly, no further action should be needed to enable Onboarding.

Users of the published WooCommerce Admin plugin need to either opt-in to using the new onboarding experience manually, or become part of the a/b test in core. See https://github.com/woocommerce/woocommerce/pull/24991 for more details on the testing implementation.

To enable the new onboarding experience manually, log-in to `wp-admin`, and go to `WooCommerce > Settings > Help > Setup Wizard`. Click `Enable` under the `New onboarding experience` heading.

You can also set the following configuration flag in your `wp-config.php`:

`define( 'WOOCOMMERCE_ADMIN_ONBOARDING_ENABLED', true );`

## New REST API endpoints

@todo

## Onboarding filters and hooks

@todo

## Options and settings

@todo

## WooCommerce.com connection and redirection

During the profile wizard, merchants can select paid product type extensions (like WooCommerce Memberships) or a paid theme. To make installation easier and to finish purchasing, it is necessary to make a [WooCommerce.com connection](https://docs.woocommerce.com/document/managing-woocommerce-com-subscriptions/). We also prompt users to connect on the task list if they chose extensions in the profile wizard, but did not finish connecting.

To make the connection from the new oboarding experience possible, we build our own connection endpoints [/wc-admin/onboarding/plugins/request-wccom-connect](https://github.com/woocommerce/woocommerce-admin/blob/61b771c2643c24334ea062ab3521073beaf50019/src/API/OnboardingPlugins.php#L298-L355) and [/wc-admin/onboarding/plugins/finish-wccom-connect](https://github.com/woocommerce/woocommerce-admin/blob/61b771c2643c24334ea062ab3521073beaf50019/src/API/OnboardingPlugins.php#L357-L417).

Both of these endpoints use WooCommerce Core's `WC_Helper_API` directly. The main difference with our connection (compared to the connection on the subscriptions page) is the addition of two additional query strings: `wccom-from=onboarding` and `calypso_env`.

`wccom-from=onboarding` is used to tell WooCommerce.com & WordPress.com that we are connecting from the new onboarding flow. This parameter is passed from the user's site to WooCommerce.com and finally into Calypso, so that the Calypso login and sign-up screens match the rest of the profile wizard (https://github.com/Automattic/wp-calypso/pull/35193). Without this parameter, you would end up on the existing WooCommerce OAuth screen. Calypso's sign-up and login is spread throughout a few different files, but searching for `wccom-from` [shows where this logic lives](https://github.com/Automattic/wp-calypso/search?q=wccom-from&unscoped_q=wccom-from).

`calypso_env` allows us to load different versions of Calypso when testing.  By default, a merchant will end up on a production version of Calypso. If we make changes to the Calypso part of the flow and want to test them, we could pass `calypso_env=development` and to be redirected to `http://calypso.localhost:3000/` instead of `https://wordpress.com`.

To change the value of `calypso_env`, set `WOOCOMMERCE_CALYPSO_ENVIRONMENT` in your `wp-config.php`. It accepts the following values: `'development', 'wpcalypso', 'horizon', 'stage'`.

To disconnect from WooCommerce.com, go to `WooCommerce > Extensions > WooCommerce.com Subscriptions > Connected to WooCommerce.com > Disconnect`.

## Calypso and Jetpack Connections

@todo

## Building the onboarding feature plugin

The `onboarding` feature flag is enabled in the main WooCommerce Admin plugin build. That means the published version of the plugin on WordPress.org contains the onboarding feature, but it is visually off by default. See the "enable onboarding" section above.

Sometimes, it may be necessary to generate a separate build of the plugin between public releases for internal testing or debugging. This can be done using the [building custom plugin builds](https://github.com/woocommerce/woocommerce-admin/blob/master/docs/feature-flags.md#building-custom-plugin-builds) feature of our build system.

* Switch to the latest master branch and pull down any changes
* Run `npm run build:release -- --slug onboarding --features '{"onboarding":true}'`
* A special `woocommerce-admin-onboarding.zip` release will be generated, containing the latest onboarding code
* Make sure to follow the directions in the "enabling onboarding" section above to properly use the build