---
post_title: Troubleshooting WooCommerce Endpoints
menu_title: Troubleshooting Endpoints
tags: how-to
---

This document outlines common troubleshooting steps for [WooCommerce Endpoints](.woocommerce-endpoints.md). 

For more information, learn how to [Customize Endpoints](./customizing-endpoint-urls.md).

## Endpoints showing 404

-   If you see a 404 error, go to **WordPress Admin** > **Settings > Permalinks** and Save. This ensures that rewrite rules for endpoints exist and are ready to be used.
-   If using an endpoint such as view-order, ensure that it specifies an order number. /view-order/ is invalid. /view-order/10/ is valid. These types of endpoints should not be in your navigation menus.

## Endpoints are not working

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

## Pages direct to wrong place

Landing on the wrong page when clicking an endpoint URL is typically caused by incorrect settings. For example, clicking 'Edit address' on your account page takes you to the Shop page instead of the edit address form means you selected the wrong page in settings. Confirm that your pages are correctly configured and that a different page is used for each section.

## How to Remove "Downloads" from My Account

Sometimes the "Downloads" endpoint on the "My account" page does not need to be displayed. This can be removed by going to **WooCommerce → Settings → Advanced → Account endpoints** and clearing the Downloads endpoint field.

![Account endpoints](https://developer.woo.com/wp-content/uploads/2023/12/Screenshot-2023-04-09-at-11.45.58-PM.png)
