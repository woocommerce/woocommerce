Scripts located in this directory are meant to be loaded on wp-admin pages outside the context of WooCommerce Admin, such as the post editor. Adding the script name to `wpAdminScripts` in the Webpack config will automatically build these scripts.

Scripts must be manually enqueued with any neccessary dependencies. For example, `onboarding-homepage-notice` uses the WooCommerce navigation package:

`wp_enqueue_script(  'onboarding-homepage-notice', Loader::get_url( 'wp-scripts/onboarding-homepage-notice.js' ), array( 'wc-navigation' ) );`