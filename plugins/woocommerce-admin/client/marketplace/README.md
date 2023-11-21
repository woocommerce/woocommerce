# Marketplace

This folder contains the components used in the Marketplace page.

The page contains two parts, the Woo.com marketplace and a list of products that the user purchased.

## Marketplace

- **Discover**: This tab contains the featured extensions and themes.
- **Browse**: This tab contains the list of all the extensions.
- **Themes**: This tab contains the list of all the themes.
- **Search**: This tab contains the search results.

### Marketplace API

The data for the Discover section is fetched from the `/wc/v3/marketplace/featured` endpoint.
Themes, extensions, and search results are fetched directly from Woo.com.

## My Subscriptions

This tab contains the list of all the extensions and themes that the user has purchased.

The list is fetched from Woo.com and allows shop owners to install, update, and connect products.
If a subscription is expired, the user will be prompted to renew it.

### My Subscriptions API

My Subscriptions data uses `/wc/v3/marketplace/subscriptions` API endpoints to list, install, connect, and update products.
A full list of endpoints can be found in the [subscriptions API source code](/plugins/woocommerce/includes/admin/helper/class-wc-helper-subscriptions-api.php).

## Project Structure

The project is structured as follows:
- **components**: Contains the React components used in the Marketplace page.
- **contexts**: Contains the React contexts used in the Marketplace page.
- **utils**: Contains the functionalities used in the Marketplace page to interact with APIs.
- **stylesheets**: Contains the shared stylesheets used in the Marketplace page.
- **assets**: Contains the images used in the Marketplace page.

## Development

This feature is part of WooCommerce Admin and uses the [same development environment.](/plugins/woocommerce-admin/README.md)