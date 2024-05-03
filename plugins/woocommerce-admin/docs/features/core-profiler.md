# Core Profiler

The Core Profiler feature is a modernized and simplified new user setup experience for WooCommerce core. It is the first thing a new merchant will see upon installation of WooCommerce. 

It requests the minimum amount of information from a merchant to get their store up and running, and suggests some optional extensions that may fulfil common needs for new stores.

There are 4 pages in the Core Profiler:

1. Introduction & Data sharing opt-in
2. User Profile - Some questions determining the user's entry point to the WooCommerce setup
3. Business Information - Store Details and Location
4. Extensions - Optional extensions that may be useful to the merchant

If the merchant chooses to install any extensions that require Jetpack, they will then be redirected to WordPress.com to login to Jetpack after the extensions page. Upon completion of that, they will be returned back to the WooCommerce Admin homescreen which contains the Task List. The Task List will provide next steps for the merchant to continue with their store setup.

## Development

The Core Profiler is gated behind the `core-profiler` feature flag, but is enabled by default. 

This feature is the first feature in WooCommerce to use XState for state management, and so naming and organisational conventions will be developed as the team's experience with XState grows.

Refer to the [XState Dev Tooling](xstate.md) documentation for information on how to use the XState visualizer to debug the state machines.

The state machine for the Core Profiler is centrally located at `./client/core-profiler/index.tsx`, and is responsible for managing the state of the entire feature. It is responsible for rendering the correct page based on the current state, handling events that are triggered by the user, triggering side effects such as API calls and handling the responses. It also handles updating the browser URL state as well as responding to changes in it.

While working on this feature, bear in mind that the state machine should interact with WordPress and WooCommerce via actions and services, and the UI code should not be responsible for any API calls or interaction with WordPress Data Stores. This allows us to easily render the UI pages in isolation, for example use in Storybook. The UI pages should only send events back to the state machine in order to trigger side effects.

## Saving and retrieving data

As of writing, the following options are saved (and retrieved if the user has already completed the Core Profiler):

- `blogname`: string

This stores the name of the store, which is used in the store header and in the browser tab title, among other places.

- `woocommerce_onboarding_profile`:
    
    ```typescript
    {
        business_choice: "im_just_starting_my_business" | "im_already_selling" | "im_setting_up_a_store_for_a_client" | undefined
        selling_online_answer: "yes_im_selling_online" | "no_im_selling_offline" | "im_selling_both_online_and_offline" | undefined
        selling_platforms: ("amazon" | "adobe_commerce" | "big_cartel" | "big_commerce" | "ebay" | "ecwid" | "etsy" | "facebook_marketplace" | "google_shopping" | "pinterest" | "shopify" | "square" | "squarespace" | "wix" | "wordpress")[] | undefined
        is_store_country_set: true | false
        industry: "clothing_and_accessories" | "health_and_beauty" | "food_and_drink" | "home_furniture_and_garden" | "education_and_learning" | "electronics_and_computers" | "other"
    }
    ```

This stores the merchant's onboarding profile, some of which are used for suggesting extensions and toggling other features. 

- `woocommerce_default_country`: e.g 'US:CA', 'SG', 'AU:VIC'

This stores the location that the WooCommerce store believes it is in. This is used for determining extensions eligibility.

- `woocommerce_allow_tracking`: 'yes' | 'no'

This determines whether we return telemetry to Automattic.

As this information is not automatically updated, it would be best to refer directly to the data types present in the source code for the most up to date information.

### API Calls

The following WP Data API calls are used in the Core Profiler:

- `resolveSelect( ONBOARDING_STORE_NAME ).getFreeExtensions()`

This is used to retrieve the list of extensions that will be shown on the Extensions page. It makes an API call to the WooCommerce REST API, which will make a call to Woo.com if permitted. Otherwise it retrieves the locally stored list of free extensions.

- `resolveSelect( COUNTRIES_STORE_NAME ).getCountries()`

This is used to retrieve the list of countries that will be shown in the Country dropdown on the Business Information page. It makes an API call to the WooCommerce REST API.

- `resolveSelect( COUNTRIES_STORE_NAME ).geolocate()`

This is used to retrieve the country that the store believes it is in. It makes an API call to the WordPress.com geolocation API, if permitted. Otherwise it will not be used.

- `resolveSelect( PLUGINS_STORE_NAME ).isJetpackConnected()`

This is used to determine whether the store is connected to Jetpack.

- `resolveSelect( ONBOARDING_STORE_NAME ).getJetpackAuthUrl()`

This is used to retrieve the URL that the browser should be redirected to in order to connect to Jetpack.

### Extensions Installation

The Core Profiler has a loading screen that is shown after the Extensions page. This loading screen is meant to hide the installation of Extensions, while also giving the user a sense of progress. At the same time, some extensions take extremely long to install, and thus we have a 30 second timeout. 

The selected extensions will be put into an installation queue, and the queue will be processed sequentially while the loader is on screen.

Beyond the 30 second timeout, the remaining plugins will be installed in the background, and the user will be redirected to the WooCommerce Admin homescreen or the Jetpack connection page.
