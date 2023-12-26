# Marketplace

This folder contains the components used in the Marketplace page found in `WooCommerce > Extensions`.

The page contains two parts, the Woo.com marketplace and a list of products the user purchased.

## Marketplace Tabs

- **Discover**: A curated list of extensions and themes.
- **Browse**: All extensions.
- **Themes**: All themes.
- **Search**: Search results.

### Marketplace API

The data for the Discover section is fetched from the `/wc/v3/marketplace/featured` endpoint. This behaves as a proxy to fetch and cache the content from the `woocommerce.com/wp-json/wccom-extensions` endpoint.

Themes, extensions, and search results are fetched directly from Woo.com.

## My Subscriptions

This tab contains the list of all the extensions and themes the WooCommerce merchant has purchased from the Woo.com Marketplace.

The merchant needs to connect the site to their Woo.com account to view this list and install, update, and enable the products.

If a subscription is expired, the merchant will be prompted to renew it.

### My Subscriptions API

My Subscriptions data uses `/wc/v3/marketplace/subscriptions` API endpoints to list, install, connect, and update products.

You can find a full list of endpoints in the [subscriptions API source code](/plugins/woocommerce/includes/admin/helper/class-wc-helper-subscriptions-api.php).

## Project Structure

The project is structured as follows:

- **components**: The React components used in the Marketplace page.
- **contexts**: React contexts.
- **utils**: Functions used to interact with APIs.
- **stylesheets**: Shared stylesheets.
- **assets**: Images.

## Development

This feature is part of WooCommerce Admin and uses the [same development environment.](/plugins/woocommerce-admin/README.md)
