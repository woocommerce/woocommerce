# WooCommerce Endpoints

**Note:** We are unable to provide support for customizations under our **[Support Policy](https://woo.com/support-policy/)**. If you need to further customize a snippet, or extend its functionality, we highly recommend [**Codeable**](https://codeable.io/?ref=z4Hnp), or a [**Certified WooExpert**](https://woo.com/experts/).

Endpoints are an extra part in the website URL that is detected to show different content when present.

For example: You may have a ‘my account’ page shown at URL **yoursite.com/my-account**. When the endpoint ‘edit-account’ is appended to this URL, making it ‘**yoursite.com/my-account/edit-account**‘ then the **Edit account page** is shown instead of the **My account page**.

This allows us to show different content without the need for multiple pages and shortcodes, and reduces the amount of content that needs to be installed.

Endpoints are located at **WooCommerce > Settings > Advanced**.

## Checkout Endpoints

The following endpoints are used for checkout-related functionality and are appended to the URL of the /checkout page:

-   Pay page – `/order-pay/{ORDER_ID}`
-   Order received (thanks) – `/order-received/`
-   Add payment method – `/add-payment-method/`
-   Delete payment method – `/delete-payment-method/`
-   Set default payment method – `/set-default-payment-method/`

## Account Endpoints

The following endpoints are used for account-related functionality and are appended to the URL of the /my-account page:

-   Orders – `/orders/`
-   View order – `/view-order/{ORDER_ID}`
-   Downloads – `/downloads/`
-   Edit account (and change password) – `/edit-account/`
-   Addresses – `/edit-address/`
-   Payment methods – `/payment-methods/`
-   Lost password – `/lost-password/`
-   Logout – `/customer-logout/`

## Customizing endpoint URLs

The URL for each endpoint can be customized in **WooCommerce > Settings > Advanced** in the Page setup section.

![Endpoints](https://woo.com/wp-content/uploads/2014/02/endpoints.png)

Ensure that they are unique to avoid conflicts. If you encounter issues with 404s, go to **Settings > Permalinks** and save to flush the rewrite rules.

## Using endpoints in menus

If you want to include an endpoint in your menus, you need to use the Links section:

![2014-02-26 at 14.26](https://woo.com/wp-content/uploads/2014/02/2014-02-26-at-14.26.png)

Enter the full URL to the endpoint and then insert that into your menu.

Remember that some endpoints, such as view-order, require an order ID to work. In general, we don’t recommend adding these endpoints to your menus. These pages can instead be accessed via the my-account page.

## Using endpoints in Payment Gateway Plugins

WooCommerce provides helper functions in the order class for getting these URLs. They are:

`$order->get_checkout_payment_url( $on_checkout = false );`

and:

`$order->get_checkout_order_received_url();`

Gateways need to use these methods for full 2.1+ compatibility.

## Troubleshooting

### Endpoints showing 404

-   If you see a 404 error, go to **WordPress Admin** > **Settings > Permalinks** and Save. This ensures that rewrite rules for endpoints exist and are ready to be used.
-   If using an endpoint such as view-order, ensure that it specifies an order number. /view-order/ is invalid. /view-order/10/ is valid. These types of endpoints should not be in your navigation menus.

### Endpoints are not working

On Windows servers, the **web.config** file may not be set correctly to allow for the endpoints to work correctly. In this case, clicking on endpoint links (e.g. /edit-account/ or /customer-logout/) may appear to do nothing except refresh the page. In order to resolve this, try simplifying the **web.config** file on your Windows server. Here’s a sample file configuration:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<configuration>
  <system.webServer>
    <handlers accessPolicy="Read, Execute, Script" />
    <rewrite>
    <rules>
      <rule name="wordpress" patternSyntax="Wildcard">
        <match url="*" />
        <conditions>
          <add input="{REQUEST_FILENAME}" matchType="IsFile" negate="true" />
          <add input="{REQUEST_FILENAME}" matchType="IsDirectory" negate="true" />
        </conditions>
        <action type="Rewrite" url="index.php" />
      </rule>
    </rules>
    </rewrite>
  </system.webServer>
</configuration>
```

### Pages direct to wrong place

Landing on the wrong page when clicking an endpoint URL is typically caused by incorrect settings. For example, clicking ‘Edit address’ on your account page takes you to the Shop page instead of the edit address form means you selected the wrong page in settings. Confirm that your pages are correctly configured and that a different page is used for each section.

### How to Remove “Downloads” from My Account

Sometimes the “Downloads” endpoint on the “My account” page does not need to be displayed. This can be removed by going to **WooCommerce → Settings → Advanced → Account endpoints** and clearing the Downloads endpoint field.

![Account endpoints](https://woo.com/wp-content/uploads/2023/04/Screenshot-2023-04-09-at-11.45.58-PM.png?w=650)
