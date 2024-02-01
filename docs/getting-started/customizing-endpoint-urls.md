---
post_title: Customizing WooCommerce Endpoint URLs
menu_title: Customizing Endpoint URLs
tags: how-to
---

Before you start, check out [WooCommerce Endpoints](./woocommerce-endpoints.md). 

## Customizing endpoint URLs

The URL for each endpoint can be customized in **WooCommerce > Settings > Advanced** in the Page setup section.

![Endpoints](https://developer.woo.com/wp-content/uploads/2023/12/endpoints.png)

Ensure that they are unique to avoid conflicts. If you encounter issues with 404s, go to **Settings > Permalinks** and save to flush the rewrite rules.

## Using endpoints in menus

If you want to include an endpoint in your menus, you need to use the Links section:

![The Links section of a menu item in WordPress](https://developer.woo.com/wp-content/uploads/2023/12/2014-02-26-at-14.26.png)

Enter the full URL to the endpoint and then insert that into your menu.

Remember that some endpoints, such as view-order, require an order ID to work. In general, we don't recommend adding these endpoints to your menus. These pages can instead be accessed via the my-account page.

## Using endpoints in Payment Gateway Plugins

WooCommerce provides helper functions in the order class for getting these URLs. They are:

`$order->get_checkout_payment_url( $on_checkout = false );`

and:

`$order->get_checkout_order_received_url();`

Gateways need to use these methods for full 2.1+ compatibility.

## Troubleshooting

### Endpoints showing 404

-   If you see a 404 error, go to **WordPress Admin** > **Settings > Permalinks** and Save. This ensures that rewrite rules for endpoints exist and are ready to be used.
-   If using an endpoint such as view-order, ensure that it specifies an order number. /view-order/ is invalid. /view-order/10/ is valid. These types of endpoints should not be in your navigation menus.

### Endpoints are not working

On Windows servers, the **web.config** file may not be set correctly to allow for the endpoints to work correctly. In this case, clicking on endpoint links (e.g. /edit-account/ or /customer-logout/) may appear to do nothing except refresh the page. In order to resolve this, try simplifying the **web.config** file on your Windows server. Here's a sample file configuration:

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

Landing on the wrong page when clicking an endpoint URL is typically caused by incorrect settings. For example, clicking 'Edit address' on your account page takes you to the Shop page instead of the edit address form means you selected the wrong page in settings. Confirm that your pages are correctly configured and that a different page is used for each section.

### How to Remove "Downloads" from My Account

Sometimes the "Downloads" endpoint on the "My account" page does not need to be displayed. This can be removed by going to **WooCommerce → Settings → Advanced → Account endpoints** and clearing the Downloads endpoint field.

![Account endpoints](https://developer.woo.com/wp-content/uploads/2023/12/Screenshot-2023-04-09-at-11.45.58-PM.png)
