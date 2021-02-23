# Testing instructions

## Unreleased

## 2.1.0

### Navigation: Reset submenu before making Flyout #6396

-   Download and activate the MailChimp plugin.
-   Turn on Navigation at Settings > Advanced > Features
-   Return to the WP dashboard
-   Hover over WooCommerce and see the flyout menu appear
-   MailChimp should not be included.

### Email notes now are turned off by default #6324

-   Create a zip for testing with `npm run zip:test`.
-   Create a `jurassic.ninja` instance.
-   Upload the plugin and activate it.
-   Update the installation date (we need a 10-day old store). You can do it with an SQL statement like this:

```
UPDATE `wp_options` SET `option_value`=UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 10 day)) WHERE `option_name` = 'woocommerce_admin_install_timestamp';
```

-   Confirm that `woocommerce_merchant_email_notifications` was not set before by `core` with a SQL statement like:

```
DELETE FROM `wp_options` WHERE `wp_options`.`option_name` = 'woocommerce_merchant_email_notifications';
```

or with wp-cli:

```
wp option delete 'woocommerce_merchant_email_notifications';
```

-   Run the cron (this tool can help [WP Crontrol](https://wordpress.org/plugins/wp-crontrol/)).
-   You should have not received an email note.
-   Verify the note `wc-admin-add-first-product-note` was added in the DB and its `status` is `unactioned`. You can use a statement like this:

```
SELECT `status` FROM `wp_wc_admin_notes` WHERE `name` = 'wc-admin-add-first-product-note';
```

or with wp-cli:

```
wp db query 'SELECT status FROM wp_wc_admin_notes WHERE name = "wc-admin-add-first-product-note"' --skip-column-names
```

-   Run the cron again.
-   The note's status should continue being `unactioned`.

### Refactor menu item mapping and sorting #6382

1. Enable the new navigation under WooCommerce -> Settings -> Advanced -> Features.
2. Navigate to a WooCommerce page.
3. Make sure all items and categories continue to work as expected.
4. Activate multiple extensions that register WooCommerce extension categories. (e.g., WooCommerce Bookings and WooCommerce Payments).
5. Favorite and unfavorite menu items.
6. Make sure the menu item order is correct after unfavoriting.
7. Create a user with permissions to see some but not all registered WooCommerce pages.
8. Check that a user without permission to access a menu item cannot see said menu item.

### Remove CES actions for adding and editing a product and editing an order #6355

1. Add a product. The customer effort score survey should not appear.
2. Edit a product. The customer effort score survey should not appear.
3. Edit an order. The customer effort score survey should not appear.

### Center the activity panel #6289

1. Narrow your screen to <782px
2. Go to WooCommerce home and orders page
3. Click on 'w' button, see that the activity panel renders as expected.

### Make sure that industry is defined in payment methods #6281

-   Start a new store, and skip the initial onboarding flow, there is a button `Skip store details` at the bottom
-   Load the `Set up payments` task, the payment options should load correctly.

### Add a new note with a link to the downloadable product doc #6277

1. Make sure your store does not have any download products.
2. Install WP Crontrol plugin.
3. Add a new download product.
4. Navigate to Tools -> Cron Events and run `wc_admin_daily`
5. Navigate to WooCommerce -> Home and confirm that the note has been added.

### Onboarding - Fixed "Business Details" error #6271

-   Check out this branch.
-   Go to the "Industry" step in the OBW and select `Food and drink`.
-   Go to the "Business Details" step and press `Free features`.
-   Press `Continue`.
-   It should work.
-   Try also selecting and unselecting some checkboxes before pressing `Continue`.

### Change `siteUrl` to `homeUrl` on navigation site title #6240

-   Go to WP settings and set the home page to My account
-   Go to WC settings and use the new navigation feature
-   Click on the header site title My Site and see that the page direct to My account

### Refactor panel with withFocusOutside #6233

-   Go to WooCommerce home page
-   Click on Display and Help button back and forth, check that the popover and the panel close as expected.
-   Check that the setup store tab continues to work.

### Move capability checks to client #6365

1. Create various non-admin users with custom capabilities. Make sure to not include the `view_woocommerce_reports` for at least one role. https://wordpress.org/plugins/leira-roles/
2. Log in as the non-admin users.
3. Check that the correct menu items are shown.
4. Check that there aren't items shown to the user they should not be able to use or interact with.
5. Enable the new navigation under WooCommerce -> Settings -> Advanced -> Features.
6. Check that the users are able to see the new navigation menu.
7. Click on various tabs in the activity panel.
8. Make sure the tabs work as expected.
9. Make sure that users without the `manage_woocommerce` permission are not able to see the "Store Setup" tab.
10. With a user that can `manage_woocommerce`, navigate to the homepage via URL and make sure the homescreen is shown. `/wp-admin/admin.php?page=wc-admin`
11. With a user that cannot `view_woocommerce_reports` make sure navigating to an analytics report does not work. `/wp-admin/admin.php?page=wc-admin&path=/analytics/overview`

### Add CES track settings tab on updating settings #6368

-   Make sure tracking is enabled in settings:

```
/wp-admin/admin.php?page=wc-settings&tab=advanced&section=woocommerce_com
```

-   Delete the option `woocommerce_ces_shown_for_actions` to make sure CES prompt triggers when updating settings.
-   Enable the logging of Tracks events to your browser dev console:

```
localStorage.setItem( 'debug', 'wc-admin:tracks' );
```

-   Go to WooCommerce > Settings, and select a top-level tab such as Products, Shipping, etc.
-   Click on `Save changes`.
-   Observe in developer console, `wcadmin_ces_snackbar_view` is logged when CES prompt is displayed.
-   In the event props, it should have a new `settings_area` key followed by the value of the settings tab you have selected.

### Add navigation intro modal #6367

1. Visit the homescreen and dismiss the original welcome modal if you haven't already.
2. Enable the new navigation under WooCommerce -> Settings -> Advanced -> Features. (This will also require opting into tracking).
3. Visit the WooCommerce Admin homescreen.
4. Note the new modal.
5. Check that pagination works as expected and modal styling is as expected.
6. Dismiss the modal.
7. Refresh the page to verify the modal does not reappear.
8. On a new site, enable the navigation before visiting the homescreen.
9. Navigate to the homescreen.
10. Note the welcome modal is shown and the navigation intro modal is not shown.
11. Refresh the page and note the nav intro modal was dismissed and never shown.

## 2.0.0

### Add the Mollie payment provider setup task #6257

-   You'll need a site that has the setup task list visible. Complete the OBW and make sure you're in a Mollie supported country (Such as United Kingdom).
-   Go to the setup payments task
-   Mollie should be listed as an option
-   Click "Set up" button on the Mollie task
-   It should install and activate the mollie payments plugin
-   The connect step should provide links to create an account or go straight to Mollie settings. (test both links work)
-   Click "continue"
-   You should arrive back at the payment provider list

### Fix: allow for more terms to be shown for product attributes in the Analytics orders report. #5868

1. Create a product attribute
2. Give the attribute more than 10 terms
3. Go to Analytics -> Orders
4. Add an attribute filter to the list, choose your attribute.
5. Go to the second input field and click on it, a result set of all your terms should appear

### Add: new inbox message - Getting started in Ecommerce - watch this webinar. #6086

-   First you'll need to make sure you meet the criteria for the note:
    1. obw is done
    2. revenue is between 0-2500
    3. do not check "setting up for client" in obw
    4. store should have no products
-   Next you need to install WP Crontrol, go to its list of cron events and click "run now" on "wc_admin_daily"
-   Confirm the new note is displayed and that the content matches that specified below:
    -   Title: Getting Started in eCommerce - webinar
    -   Copy: We want to make eCommerce and this process of getting started as easy as possible for you. Watch this webinar to get tips on how to have our store up and running in a breeze.
    -   CTA leads to: https://youtu.be/V_2XtCOyZ7o
    -   CTA label: Watch the webinar

### Update: store deprecation welcome modal support doc link #6094

-   Starting with a fresh store (or by deleting the woocommerce_task_list_welcome_modal_dismissed option), visit /wp-admin/admin.php?page=wc-admin. You should see the standard welcome modal.
-   Add &from-calypso to the URL. You should see the Calypso welcome modal.
-   Notice "Learn more" links to https://wordpress.com/support/new-woocommerce-experience-on-wordpress-dot-com/

### Enhancement: Allowing users to create products by selecting a template. #5892

-   Load new store and finish the Wizard
-   Go to the `Add my products` task
-   Click the `Start with a template` option, and select either a physical, digital, variable product
-   Once you click `Go`, it should redirect you to an edit page of the new post, with the data from the sample-data.csv (mentioned in the original ticket). Only the title is missing, as it is saved as auto-draft.
-   You should be able to save the new product as draft or publish it.
-   You should be able to exit without making any changes, and not having the product show up as draft in your product list.
    -   Create new product from template
    -   Wait until redirected
    -   Without saving go to the **Products > all products** page, the new product should not be displayed.

### Update: Homescreen layout, moving Inbox panel for better interaction. #6122

-   Create a new woo store, and finish the onboarding wizard.
-   Go to the home screen, and make sure the panels follow this order:
-   Two column:
    -   Left column: store setup and/or management tasks + inbox panel
    -   Right column: stats overview + store management shortcuts (only shows when setup tasks is hidden)
-   Single column:
    -   store setup tasks, inbox panel, stats overview, store management links (only visible when setup tasks is hidden).
-   Hide the setup tasks list, and see if the store management links show up in the right place.

### Enhancement: Use the new Paypal payments plugin for onboarding. #6261

-   Create new woo store, and finish the onboarding wizard
-   Go to the home screen, and click the **Set up payments** task. **Paypal Payments** option should be listed as an option, with a **Set up** button.
-   Click **Set up** on the Paypal plugin.
-   It should automatically start the **Install** step, and install and enable the [Paypal Payments](https://woocommerce.com/products/woocommerce-paypal-payments/) plugin.
-   For Paypal Payments version greater then `1.1.0`.
    -   For the second step it should show a `Connect` button
    -   Click on **Connect** and a window should popup for Paypal, follow this until finished. The last button is - Go back to Woocommerce Developers
    -   Once done, the page should reload, and briefly show the setup screen again, it should then finish and go back to the payment list.
    -   Once on the payment list, the `Set up` button should be gone, and instead show a toggle, that is set to enabled.
        -   The enable/disable button should be correctly reflected in the Woocommerce payment settings screen as well.
-   For Paypal Payments version `1.1.0` and below
    -   For the second step it will show the manual fields (merchant email, merchant id, client id, client secret).
    -   Check if the help links work below, they should help with finding the above credentials.
        -   If you have a business account set up, you can find the credentials in these two places
        -   [Get live app credentials](https://developer.paypal.com/developer/applications/)
        -   [Merchant id](https://www.paypal.com/businessmanage/account/aboutBusiness)
    -   Fill in the credentials and click **Proceed**, this should succeed and redirect you to the Payment options list
    -   The **Set up** button should be replaced by a toggle, that is set to enabled.
        -   The enable/disable button should be correctly reflected in the Woocommerce payment settings screen as well.

Once everything is configured and enabled do a client test

-   Make sure you have added a product and store homescreen (Finish the **Personalize my store** task)
-   Navigate to one of the products and add it to the cart
-   Click **go to checkout**
-   Paypal should be one of the options to pay
-   Filling in your billing/shipping info then click pay with **Paypal**
-   The paypal pay window should pop up correctly without errors.
