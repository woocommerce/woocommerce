---
post_title: Understanding WooCommerce Endpoints
menu_title: WooCommerce Endpoints
---

Endpoints are an extra part in the website URL that is detected to show different content when present.

For example: You may have a 'my account' page shown at URL **yoursite.com/my-account**. When the endpoint 'edit-account' is appended to this URL, making it '**yoursite.com/my-account/edit-account**' then the **Edit account page** is shown instead of the **My account page**.

This allows us to show different content without the need for multiple pages and shortcodes, and reduces the amount of content that needs to be installed.

Endpoints are located at **WooCommerce > Settings > Advanced**.

## Checkout Endpoints

The following endpoints are used for checkout-related functionality and are appended to the URL of the /checkout page:

-   Pay page - `/order-pay/{ORDER_ID}`
-   Order received (thanks) - `/order-received/`
-   Add payment method - `/add-payment-method/`
-   Delete payment method - `/delete-payment-method/`
-   Set default payment method - `/set-default-payment-method/`

## Account Endpoints

The following endpoints are used for account-related functionality and are appended to the URL of the /my-account page:

-   Orders - `/orders/`
-   View order - `/view-order/{ORDER_ID}`
-   Downloads - `/downloads/`
-   Edit account (and change password) - `/edit-account/`
-   Addresses - `/edit-address/`
-   Payment methods - `/payment-methods/`
-   Lost password - `/lost-password/`
-   Logout - `/customer-logout/`

## Learn more

- [Customizing endpoint URLs](./customizing-endpoint-urls.md)
- [Troubleshooting endpoints](./troubleshooting-endpoints.md)
