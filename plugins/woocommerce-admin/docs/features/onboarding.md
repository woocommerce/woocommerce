# WooCommerce Onboarding (DEPRECATED)

**Refer to the new [Core Profiler](./core-profiler.md) documentation for the latest onboarding experience.**

**Some parts of this documentation are still relevant and will be gradually moved to the new Core Profiler documentation.**

The onboarding feature is a reimagined new user setup experience for WooCommerce core. It contains two sections aimed at getting merchants started with their stores. The merchant begins with the "profile wizard", which helps with initial steps like setting a store address, making extension recommendations, and connecting to Jetpack for additional features. Once the profile wizard is complete, the merchant can purchase & install optional extensions via Woo.com, before continuing to the "task list". From the task list, merchants are walked through a variety of items to help them start selling, such as adding their first product and setting up payment methods.

## Enabling Onboarding

If you run the development version of WooCommerce Admin from GitHub directly, no further action should be needed to enable Onboarding.

Users of the published WooCommerce Admin plugin need to either opt-in to using the new onboarding experience manually, or become part of the a/b test in core. See [https://github.com/woocommerce/woocommerce/pull/24991](https://github.com/woocommerce/woocommerce/pull/24991) for more details on the testing implementation.

To enable the new onboarding experience manually, log-in to `wp-admin`, and go to `WooCommerce > Settings > Help > Setup Wizard`. Click `Enable` under the `New onboarding experience` heading.

## New REST API endpoints

To power the new onboarding flow client side, new REST API endpoints have been introduced. These are purpose built endpoints that exist under the `/wc-admin/onboarding/` namespace, and are not meant to be shipped in the core rest API package. The source is stored in `src/API/Plugins.php`, `src/API/OnboardingProfile.php`, and `src/API/OnboardingTasks.php` respectively.

* POST `/wc-admin/plugins/install` - Install requested plugins.
* GET `/wc-admin/plugins/active` - Returns a list of the currently active plugins.
* POST `/wc-admin/plugins/activate` - Activates the requested plugins. Multiple plugins can be passed to activate at once.
* GET `/wc-admin/plugins/connect-jetpack` - Generates a URL for connecting to Jetpack. A `redirect_url` is accepted, which is used upon a successful connection.
* POST `/wc-admin/plugins/request-wccom-connect` - Generates a URL for the Woo.com connection process.
* POST `/wc-admin/plugins/finish-wccom-connect` - Finishes the Woo.com connection process by storing the received access token.
* POST `/wc-admin/plugins/connect-square` - Generates a URL for connecting to Square during the payments task.
* GET `/wc-admin/onboarding/profile` - Returns the information gathered during the profile wizard. See the `woocommerce_onboarding_profile_properties` array for a list of fields.
* POST `/wc-admin/onboarding/profile` - Sets data for the profile wizard. See the `woocommerce_onboarding_profile_properties` array for a list of fields.
* POST `/wc-admin/onboarding/tasks/import_sample_products` - Used for importing sample products during the appearance task.
* POST `/wc-admin/onboarding/tasks/create_homepage` - Used for creating a homepage using Gutenberg templates.

## Onboarding filters

* `woocommerce_admin_onboarding_profile_properties` filters the properties we track as part of the profile wizard (such as information from store/business details steps). When the `completed` property is set to true, the profile wizard is completely dismissed and hidden.
* `woocommerce_admin_onboarding_industries` filters the list of allowed industries displayed in the profile wizard.
* `woocommerce_admin_onboarding_industry_image` filters the images used for homepage templates in the appearance task. When creating a homepage, example images are used based on industry. These images are stored in `images/onboarding`.
* `woocommerce_admin_onboarding_product_types` filters the product types displayed in the profile wizard.
* `woocommerce_admin_onboarding_plugins_whitelist` filters the list of plugins that can installed & activated via onboarding. This acts as a whitelist so only certain plugins can be used via the `/wc-admin/onboarding/profile/install` and `/wc-admin/onboarding/profile/activate` endpoints.
* `woocommerce_admin_onboarding_themes` filters the themes displayed in the profile wizard.
* `woocommerce_admin_onboarding_jetpack_connect_redirect_url` filters the Jetpack connection redirect URL outlined in the Jetpack connection section below.
* `woocommerce_admin_onboarding_task_list` filters the list of tasks on the task list dashboard. This allows extensions to add new tasks. See [the extension docs](https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce-admin/docs/examples/extensions) for an example of how to do this.
* `woocommerce_rest_onboarding_profile_collection_params` filters the collection parameters for requests to  `/wc-admin/onboarding/profile`.
* `woocommerce_rest_onboarding_profile_object_query` filters the query arguments for requests to `/wc-admin/onboarding/profile`.
* `woocommerce_rest_onboarding_prepare_onboarding_profile` filters the response for requests to `/wc-admin/onboarding/profile`.

## Options and settings

A few new WordPress options have been introduced to store information and settings during setup. It may be necessary to manual delete these options from your `wp_options` database to test a certain task or feature.


* `woocommerce_task_list_hidden_lists`. This option houses the task lists that have been hidden from view by the user.
* `woocommerce_task_list_welcome_modal_dismissed`. This option is used to show a congratulations modal during the transition between the profile wizard and task list.

We also use existing options from WooCommerce Core or extensions like WooCommerce Shipping & Tax or Stripe. The list below may not be complete, as new tasks are introduced, but you can generally find usage of these by searching for the [getOptions selector](https://github.com/woocommerce/woocommerce/search?q=getOptions&unscoped_q=getOptions).

* `woocommerce_setup_jetpack_opted_in` and `wc_connect_options` are both used to control Jetpack's Terms of Service opt-in, which is necessary to set for a user during the connection process, so that they can use services like automated tax rates.
* `woocommerce_allow_tracking` is used to control Tracks opt-in, allowing us to gather usage data from WooCommerce Admin and WooCommerce core.
* `woocommerce_demo_store` and `woocommerce_demo_store_notice` are used on the appearance task for the store notice step.
* `woocommerce_ppec_paypal_settings` and `woocommerce_stripe_settings` are used in the payments task and connection steps.

## Woo.com Connection

During the profile wizard, merchants can select paid product type extensions (like WooCommerce Memberships) or a paid theme. To make installation easier and to finish purchasing, it is necessary to make a [Woo.com connection](woocommerce.com/document/managing-woocommerce-com-subscriptions/). We also prompt users to connect on the task list if they chose extensions in the profile wizard, but did not finish connecting.

To make the connection from the new onboarding experience possible, we build our own connection endpoints [/wc-admin/plugins/request-wccom-connect](https://github.com/woocommerce/woocommerce/blob/feba6a8dcd55d4f5c7edc05478369c76df082293/plugins/woocommerce/src/Admin/API/Plugins.php#L419-L476) and [/wc-admin/plugins/finish-wccom-connect](https://github.com/woocommerce/woocommerce/blob/feba6a8dcd55d4f5c7edc05478369c76df082293/plugins/woocommerce/src/Admin/API/Plugins.php#L478-L538).

Both of these endpoints use WooCommerce Core's `WC_Helper_API` directly. The main difference with our connection (compared to the connection on the subscriptions page) is the addition of two additional query string parameters:

* `wccom-from=onboarding`, which is used to tell Woo.com & WordPress.com that we are connecting from the new onboarding flow. This parameter is passed from the user's site to Woo.com and finally into Calypso, so that the Calypso login and sign-up screens match the rest of the profile wizard [https://github.com/Automattic/wp-calypso/pull/35193](https://github.com/Automattic/wp-calypso/pull/35193). Without this parameter, you would end up on the existing WooCommerce OAuth screen.
* `calypso_env` allows us to load different versions of Calypso when testing.  See the Calypso section below.

To disconnect from Woo.com, go to `WooCommerce > Extensions > Woo.com Subscriptions > Connected to Woo.com > Disconnect`.

## WordPress.com Connection

Using a WordPress.com connection in WooCommerce Shipping & Tax allows us to offer additional features to new WooCommerce users as well as simplify parts of the setup process. For example, we can do automated tax calculations for certain countries, significantly simplifying the tax task. To make this work, the user needs to be connected to a WordPress.com account. This also means development and testing of these features needs to be done on a WPCOM connected site. Search the MGS & the Field Guide for additional resources on testing WordPress.com with local setups.

We have a special WordPress.com connection flow designed specifically for WooCommerce onboarding, so that the user feels that they are connecting as part of a cohesive experience. To access this flow, we use the [/wc-admin/onboarding/plugins/jetpack-authorization-url](https://github.com/woocommerce/woocommerce/blob/1a9c1f93b942f682b6561b5cd1ae58f6d5eea49c/plugins/woocommerce/src/Admin/API/OnboardingPlugins.php#L240C2-L274) endpoint.

We use the Jetpack Connection package's `Manager::get_authorization_url()` function directly, but add the following two query parameters:

* `calypso_env`, which allows us to load different versions of Calypso when testing. See the Calypso section below.
* `from=woocommerce-services`, which is used to conditionally show the WooCommerce-themed authorization process [https://github.com/Automattic/wp-calypso/pull/35193](https://github.com/Automattic/wp-calypso/pull/35193). Without this parameter, you would end up in the normal Jetpack authorization flow.

The user is prompted to install and connect to Jetpack as the first step of the profile wizard. If the user hasn't connected when they arrive at the task list, we also prompt them on certain tasks to make the setup process easier, such as the shipping and tax steps.

To disconnect from WordPress.com, install Jetpack, then go to `Jetpack > Dashboard > Connections > Site connection > Manage site connection > Disconnect`. You can remove Jetpack after you disconnect.

## Calypso

### `calypso_env`

Both the Woo.com & Jetpack connection processes (outlined below) send the user to [Calypso](https://github.com/Automattic/wp-calypso), the interface that powers WordPress.com, to sign-up or login.

By default, a merchant will end up on a production version of Calypso [https://wordpress.com](https://wordpress.com). If we make changes to the Calypso part of the flow and want to test them, we can do so with a `calypso_env` query parameter passed by both of our connection methods.

To change the value of `calypso_env`, set `WOOCOMMERCE_CALYPSO_ENVIRONMENT` to one of the following values in your `wp-config.php`:

* `production` which will send you to `https://wordpress.com`
* `development` which will send you to `http://calypso.localhost:3000/`
* `wpcalypso` which will send you to `http://wpcalypso.wordpress.com/`
* `horizon` which will send you to `https://horizon.wordpress.com`
* `stage` which will send you to `https://wordpress.com` (you must be proxied)

### Feature Flags

Logic for the Calypso flows are gated behind two separate [Calypso feature flags](https://github.com/Automattic/wp-calypso/tree/trunk/config#feature-flags). Since Calypso's login, sign-up, and Jetpack authentication code is spread throughout a few different files, searching for these feature flags is the best way to locate parts of the flow for development.

* [`woocommerce/onboarding-oauth`](https://github.com/Automattic/wp-calypso/search?q=woocommerce%2Fonboarding-oauth&unscoped_q=woocommerce%2Fonboarding-oauth), which enables the updated Woo.com connection flow.
* [`jetpack/connect/woocommerce`](https://github.com/Automattic/wp-calypso/search?q=%22jetpack%2Fconnect%2Fwoocommerce%22&unscoped_q=%22jetpack%2Fconnect%2Fwoocommerce%22), which enables the updated Jetpack connection flow.


### Testing

If you are running the development version of WooCommerce Admin, and have [`WP_DEBUG`](https://codex.wordpress.org/WP_DEBUG) set to `true`, two Calypso connection buttons are displayed under the `WooCommerce > Settings > Help > Setup Wizard` menu, making it easier to access and test the flows.
