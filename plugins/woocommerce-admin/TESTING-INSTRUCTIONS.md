# Testing instructions

## Unreleased

### Fix category report query returns invalid net sales

1. Create a new store and finish the Onboarding flow
2. Go to **Products > Add New** and create a product called **Hoodie with Pocket** with the price $35
3. Create a new category called **Hoodie** with **Clothing** as the parent category in the **Product categories** on the right.
4. Select **Clothing** and **Hoodie with Pocket** as well and click **Update**
5. Create an order with a single item of **Hoodie with Pocket** (keep note of the total price)
6. Run the action scheduler (make sure all are run)
7. Go to **Analytics > Overview** and scroll down to the **Leaderboards**
8. Observe that the **Clothing** category has only **1** items sold and net sales is $35
9. Click on **Clothing** it will redirect to the Categories page and show the correct numbers
10. Now click on **Analytics > Categories** again and scroll down to the table
11. Observe that the **Clothing** category has only **1** items sold and net sales is $35

## 3.1.0

### Inbox - 320 character limit

On a new site, with English language settings:

1. Go to WooCommerce home screen
2. See that all Inbox notes are short in length (aproximately less than 320 characters).

### OBW: Hide the extensions header when no available plugins in the category

1. In a new JN site with WooCommerce, install WCAdmin 3.0.0-beta.1 or WCAdmin on the main branch
2. Go to setup wizard
3. Choose "Johor - Malaysia" as store country
4. Go through all steps until "Business details"
5. Go to "Free features" tab
6. Observe the "GET THE BASICS" header is NOT shown without any plugins

### OBW: Fix free extensions list isn't updated after store location or industry is changed

**Change store Industry**

1. Checkout to this branch
2. Go to the setup wizard
3. Choose any store location that supports WCPay, such as any state in the US
4. In the Industry step, make sure to not select CBD
5. Proceed through the setup until the Business Details step
6. Go to the "Free features" tab
7. Observe **WooCommerce Payments** is displayed in the suggested extensions
8. Without refreshing, go back to the **Industry** step
9. Select **CBD industry** and click on continue until Business Details step again
10. In the extension list, observe that WooCommerce Payments is **NOT** displayed.

**Change store country**

11. Repeat steps 3~7
12. Without refreshing, go back to the **Store Details** step
13. Choose any store location that **doesn't** supports WCPay, such as Malaysia (MY).
14. Go to Business Details step again
15. In the extension list, observe that WooCommerce Payments is **NOT** displayed.

### Fix PHP Warning on 'Add new product' page

1. On a Jurassic Ninja site.
2. Go to **WooCommerce** > **Home**.
3. Press **Add my products** in the task list.
4. Press **Add manually**.
5. No PHP warning should be visible.

## 3.0.0

### Inbox - Read notes

1. Go to WooCommerce home screen
2. Interact with a note by clicking on its title or action buttons.
3. Return to the WooCommerce home screen
4. See that the note is in a read state, with duller appearance.

### Inbox - Dismiss single note

1. Go to WooCommerce home screen
2. Dismiss a single note using the "Dismiss" button when hovering over a note.
3. See the "Message dismissed" notification.
4. Click "Undo" on the notification.
5. See the note returns to inbox.
6. Dismiss the note again.
7. Re-load the WooCommerce home screen.
8. See that the note no longer appears.

### Inbox - Activity menu

1. Go to WooCommerce products screen
2. On Menu bar, see that "Activity" menu item exists in top right corner.
3. Click "Activity" menu item.
4. See notes appear in panel.

### Inbox - Dismiss all notes

1. Go to WooCommerce home screen
2. On "Inbox" heading panel, click kebab menu (3 dots).
3. Click "Dismiss all".
4. On confirmation dialog, press "Cancel" button.
5. See that notes still remain.
6. Dismall all notes again and press "Yes, dismiss all" on confirmation dialog.
7. See that all notes are removed.

### Onboarding Workflow - Add number of employees field

1. Go to step 4 of the OBW (Business details).
2. Under `Currently selling elsewhere?` select any option other than "No".
3. A drop-down list with the following options should be visible:

```
It's just me
<10
10-50
50-250
+250
I'd rather not say
```

4. Select one of those options and fill out the rest of the options.
5. Open the browser devtools, go to the `Console` and enable the debug messages. You can do this by running `localStorage.setItem( 'debug', 'wc-admin:*' );` in the `Console` and looking for the verbose console messages.
6. Verify that the event `wcadmin_storeprofiler_store_business_details_continue_variant` is recorded with the prop `number_employees` after pressing `Continue`.

## 2.9.0

### Add Avalara to tax task #7874

**Avalara supported, WooCommerce Tax supported**

1. Select an Avalara and WC Tax supported country (e.g., `US`) for your store's country
2. Visit the tax task.
3. Make sure your shown the "WooCommerce Tax" and "Avalara" options in the task list

**Avalara supported, WooCommerce Tax not supported**

1. Install the TaxJar plugin so that WC Tax is not supported and set your country to an Avalara supported country
2. Visit the task tax.
3. Make sure you are shown only the partner card of Avalara

**Avalara not supported, WooCommerce Tax not supported**

1. Set your store country to one not supported by Avalara or WC Tax (e.g., New Caledonia)
2. Visit the task tax.
3. Make sure you are immediately shown the manual set up flow with the "Configure" button

**Partner actions**

1. Visit the task tax with an Avalara supported country
2. Click Avalara and check that a new tab opens with the WCCOM plugin page
3. Back on the task tax, click on WooCommerce Tax
4. Make sure you are dropped into the old configuration flow (which should be identical to the old flow)

**Events**

1. Enter `localStorage.setItem( 'debug', 'wc-admin:*' );` in your browser's console
2. Set your store's country to an Avalara supported country
3. Note the `wcadmin_tasklist_tax_view_options` event occurs
4. Click on each of the partner action buttons
5. Make sure that `wcadmin_tasklist_tax_select_option` is recorded with the respective `selected_option` partner key.

**Completion**

1. Create a fresh site without ever having set any taxes
2. Note the task is incomplete
3. Install the WC Avalara plugin
4. Check that the task is now marked complete

## 2.8.0

### Store Profiler and Product task - include Subscriptions #7734

##### Non US stores

1. Deactivate and delete `WooCommerce Payments` if you have it installed.
2. Go to step one of the store profiler and select `France` (or any country other than the US) as the store `Country / Region`.
3. Go to step three of the store profiler (`Product Types`).
4. Verify `Subscriptions` is shown as a paid extension (with a price chip).
5. Check `Subscriptions` and continue with the OBW.
6. Go back to the `Home` screen by pressing `Skip setup store details` in step one of the store profiler. Check that the task item `Add Subscriptions to my store` is visible in the setup task list.
7. Press `Add my products` in the setup task list.
8. Select `Start with a template`. Verify that the option `Subscription product` is not visible in the popup.

##### US stores

9. Deactivate and delete `WooCommerce Payments`.
10. Go to step one of the store profiler and select `US` as the store `Country / Region`.
11. Go to step three of the store profiler (`Product Types`).
12. Verify `Subscriptions` is shown as free (without a price chip). Also, verify that the text

```
The following extensions will be added to your site for free: WooCommerce Payments. An account is required to use this feature
```

is visible at the bottom when `WooCommerce Payments` is not installed.

![screenshot-one wordpress test-2021 09 30-14_12_58](https://user-images.githubusercontent.com/1314156/135506696-b7812f7e-437f-4d89-956a-b73248f70f6b.png)

13. Check `Subscriptions` and press `Continue` and verify that the `WooCommerce Payments` plugin is installed and activated and it's not shown in the `Free features` list

![screenshot-one wordpress test-2021 09 30-14_32_20](https://user-images.githubusercontent.com/1314156/135506727-d8888f2b-3424-4cf5-a4bf-b67a14a198b6.png)

14. Verify that the `WooCommerce Payments` plugin is being shown in the `Free features` list when the store country is other than the `US`.

15. Go back to the `Home` screen by pressing `Skip setup store details` in step one of the store profiler. Check that the task item `Add Subscriptions to my store` is not visible in the setup task list. It should be visible if the store is from any country other than the `US`.

![screenshot-one wordpress test-2021 09 30-14_39_28](https://user-images.githubusercontent.com/1314156/135506770-91571f8f-2e2e-43a7-b092-b9e5fdf56df8.png)

16. Press `Add my products` in the setup task list.
17. Select `Start with a template`. Verify that the option `Subscription product` is visible in the popup

![screenshot-one wordpress test-2021 09 30-14_35_22](https://user-images.githubusercontent.com/1314156/135506748-0b7bdce5-b006-47f9-9289-03ed26e4950c.png)

18. Select `Subscription product` and press `Ok`. You should have been redirected to `post-new.php?post_type=product&subscription_pointers=true`.

## 2.7.1

### Add Newsletter Signup #7601

-   Start OBW and set up your browser console to monitor tracks. To do this, run `localStorage.setItem( 'debug', 'wc-admin:*' );`
-   Observe "Get tips, product updates and inspiration straight to your mailbox" checkbox and "Email address" field in the Store Details step.
-   Checking the checkbox should make the email field required, you should not be able to continue if it's not filled.
-   Fill in the email address field with a valid email and click on continue.
-   Observe in the track `wcadmin_storeprofiler_store_details_continue` with prop `email_signup` that appropriately flags if the user agreed to receive marketing emails.
-   Continue until Business Features step.
-   Observe the "I'm setting up a store for a client" checkbox in the step.
-   Click on continue.
-   Observe in the track `wcadmin_storeprofiler_store_business_details_continue_variant` with prop `setup_client` that appropriately flags if the user is setting up store for a client.

### Making business details sticky in OBW #7426

1. Start out with a fresh store
2. Start the on-boarding wizard and move through to the "Business details" step
3. Fill out items, and then hit Continue to move to the "Free features" tab
4. Then, before hitting Continue again, click the "Business details" tab above to move back to that step
5. Confirm that the previously selected values are still correct

### Show Pinterest for WooCommerce in `Marketing > Installed` #7417

_Prerequisite_: This requires the forthcoming native [**Pinterest for WooCommerce** extension](https://github.com/saucal/pinterest-for-woocommerce) (private repo – may not be available).

If **Pinterest for WooCommerce** is installed, marketing screen should show status info.

1. Install **Pinterest for WooCommerce** extension. Don't activate it.
1. Go to `WooCommerce > Marketing`.
1. Confirm there is a row for Pinterest in `Installed marketing extensions` with `Activate` button.
1. Activate the extension by clicking the button, or via normal `WordPress > Plugins` screen.
1. Go to `WooCommerce > Marketing`.
1. Confirm the Pinterest row guides merchant to set up the extension (if not yet set up) or links to settings and docs.

Marketing screen should be unaffected if **Pinterest for WooCommerce** is not installed.

1. Ensure **Pinterest for WooCommerce** is not installed.
1. Go to `WooCommerce > Marketing`.
1. Should look and work the same as in previous versions.

### Fix end date for last periods #6584

1. Update your system clock to March 2021
2. Create a completed order on 29th February 2020
3. Go to Analytics > Revenue
4. In the date range filter, select "Last Month" preset and compare to "Previous Year"
5. Observe that 29th February sales are included
6. In the date range filter, select "Last Week" preset and compare to "Previous Year"
7. Observe that the end date is the same as the current year's end date
8. In the date range filter, select "Last Quarter" preset and compare to "Previous Year"
9. Observe that the end date is the same as the selected quarter and subtract 1 year
10. In the date range filter, select "Last Year" preset and compare to "Previous Year"
11. Observe that the end date is the same as the selected year and subtract 1 year

## 2.6.0

### Match stock status value in CSV download to the table #7284

1. Clone this branch and run npm start
2. Add some products and set stock value.
3. Place an order and make it completed.
4. Navigate to Analytics -> Stocks
5. Click the Download button.
6. Open the downloaded file and confirm the status values match the table.

### Add marketing extensions task to task list #7383

1. Navigate to the task list and click on the "Set up marketing tools" task.
2. Click on "Get started" next to any of the extensions.
3. Make sure the extension is installed and moved to a separate card "Installed marketing extensions."
4. Click on "Manage" next to an installed and activated extension.
5. Make sure you're redirect to the respective extension settings page.
6. Navigate back to the task list and make sure the marketing task is marked as complete.

## 2.5.0

### Fix WC Home crash when the Analytics is disabled #7339

1. Navigate to WooCommerce -> Settings -> Advanced -> Features. Uncheck Analytics and save the changes.
2. Navigate to WooCommerce -> Home and confirm the page loads without an error.

### Fix Fix links on the dismiss dropdown are not clickable #7342

Please make sure to test it on Safari as well.

1. Navigate to WooCommerce -> Home
2. Click [ Dismiss ] button
3. Confirm that the links are clickable

### Fix undefined method error when setting up WC Tax #7344

1. Set up your store with US address to make sure automated tax is supported.
2. Install "WooCommerce Shipping & Tax" plugin.
3. Go to WooCommerce > Home > Set up tax.
4. Click on "Yes please"
5. Confirm that no error has occurred and you're redirected to the home screen.

### Fix missing translation strings for CES #7270

1. Navigate to Settings -> General and change the site language to a non-English (I've used Espanol for testing purposes).
2. You might need to update the language file if it's your first time using the selected language. Update the language file from Dashboard -> Updates
3. Go to WooCommerce -> Settings
4. Click the [ Save Changes ] button to trigger the CES modal.
5. Confirm the modal has correct translations (Refer to the screenshots)

### Add missing translation strings in the business features section #7268

1. Checkout this branch and run `npm start`
2. Navigate to Settings -> General and change the site language to non-English (I've used Espanol for testing purposes)
3. You might need to download the new language in Dashboard -> Updates
4. Navigate to wp-admin/admin.php?page=wc-admin&path=%2Fsetup-wizard&step=business-features and confirm the translation is working as expected.

### Fix inbox note dismiss dropdown not closing on Safari #7278

1. Checkout this branch and run npm start
2. Navigate to WooCommerce -> Home
3. Click "Dismiss" on a note. Confirm the position of the popover is correct.
4. Click anywhere outside of the popover content and confirm the popover is closed.

### Add TableSummaryPlaceholder to support skeleton loading #7294

1. Visit any page using element Table to see this update.
2. For example, Products, Orders, Revenue under Analytics; or Transactions, Deposits under Payments.
3. While loading, scrolling down to the bottom of the table to see the placeholder of TableSummary.

### Sync the category lookup table when a new category gets created #7290

1. Navigate to Products -> Add New
2. Add a new category on the Add New page and assign the product to it.
3. Place an order with the product
4. Navigate to Analytics -> Categories
5. You should see the category.

### Remove facebook extension from onboarding extensions fallback list #7287

1. Go to the OBW and look at the Free features tab
1. Observe no Facebook extension in the list

### "Terms of service" link disappears from "Set up Tax" screen #7269

1. Go to OBW setup wizard.
2. Uncheck the "WooCommerce shipping" and "WooCommerce Tax" options at Free features step.
3. Complete the OBW setup.
4. Go to WooCommerce->Home.
5. Click on "Set up Tax" option on Task list.
6. TOS should not blink.

### Use saved values if available when switching tabs #7226

1. Start onboarding wizard and continue to step 4.
2. Enter selections for business details and choose "Continue"
3. Select the tab "Business details" to go back
4. Confirm that the previously selected values are shown.

### Change the emailed report file name #7178

**Confirm the default behaviour remains the same**

1. Create a new store and install the [WP Mail Logging by MailPoet plugin](https://wordpress.org/plugins/wp-mail-logging/)
2. Go to Analytics -> Revenue and change the date range to last month
3. Click the download button and make sure you see the "Your revenue report will be emailed to you" notification
4. Go to Tools -> Scheduled Action and run the newly created `woocommerce_admin_report_export` action. After that is complete, run the `woocommerce_admin_email_report_download_link` action.
5. Go to Tools -> WP Mail Log and check the latest email. The URL linked to the "Download your Revenue report" should work as usual. The URL will be something like `filename=wc-revenue-report-export-16236128226138`

**Confirm the new filter is working**

1. Add this code to the `woocommerce-admin.php` file

```php
add_filter( 'woocommerce_admin_export_id', function ($export_id) {
	return 'different_export_id';
} );
```

2. Repeat the same steps from above. The filename in the link now should be `different_export_id`.

### Payment gateway suggestions feature

1. Navigate to the homescreen via WooCommerce -> Home
2. Click on "Set up payments"

#### How to test

Individual payment gateway plugins dictate the settings and connection flow. For testing purposes, we'll test both the default behavior of the gateway and the enhanced configuration behavior.

1. On the payments task, click "Set up" or "Enable" next to a gateway
2. Note that the gateway is installed if it requires a plugin
3. On the connection step, a button should be presented that links to the gateway's (legacy) settings screen
4. Delete the plugin
5. Install some of the payment gateways from the links below. They don't need to be activated, but the folder names should match those on WordPress.org to avoid conflicts.
6. If setting fields are shown, make sure that validation works, input is saved, and is persisted on page refresh. Make sure the gateway is marked as enabled and not labeled "Requires setup" if all fields are completed.
7. If the "Connect" button is shown, follow the connection flow. Make sure that you are returned to the payments task and that the gateway is enabled and marked as configured.
8. Remove some settings manually under the payment gateway's legacy settings screen. Make sure the gateway is no longer marked as configured.

-   Klarna - https://github.com/woocommerce/woocommerce-admin/files/6880208/klarna-checkout-for-woocommerce.zip
-   PayFast - https://github.com/woocommerce/woocommerce-admin/files/6880205/woocommerce-payfast-gateway.zip
-   PayPal - https://github.com/woocommerce/woocommerce-admin/files/6880109/woocommerce-paypal-payments.zip
-   RazorPay - https://github.com/woocommerce/woocommerce-admin/files/6880201/woo-razorpay.zip
-   Stripe - https://github.com/woocommerce/woocommerce-admin/files/6880197/woocommerce-gateway-stripe.zip
-   MercaoPago - https://github.com/woocommerce/woocommerce-admin/files/6880192/woocommerce-mercadopago.zip
-   Square - https://github.com/woocommerce/woocommerce-admin/files/6880117/woocommerce-square.zip
-   Eway - https://github.com/woocommerce/woocommerce-admin/files/6880111/woocommerce-gateway-eway.zip

##### PayFast

1. Set your country to South Africa in WooCommerce->Settings
2. Don't select CBD as an industry during onboarding

##### Paystack

1. Set your country to South Africa in WooCommerce->Settings
2. Don't select CBD as an industry during onboarding
3. Complete the payment tasks
4. Make sure that `ZAR` is the site currency after configuration of Paystack and that "test mode" is turned off in settings

##### Stripe

1. Set your store country to a Stripe supported country (e.g., US - https://stripe.com/global )
2. Don't select CBD as an industry during onboarding
3. Make sure you're using a site with https
4. Attempt to use the oauth connection flow to enable the gateway, making sure you are returned to the site and connection is successful
5. Remove the connection and use a non-https site
6. Check that the manual settings configuration flow is shown

##### PayPal

1. Set your store country to any country except India
2. Don't select CBD as an industry during onboarding
3. Make sure the PayPal connection flow is shown and works as expected
4. In Chrome, open the console "Network" tab and right-click on the `get-params` request and select "Block request URL"
5. Refresh the page and note that the manual settings flow is shown

##### Klarna

1. Set your store country to one of the following: `SE, FI, NO`
2. Don't select CBD as an industry during onboarding

##### Mollie

1. Set your store country to one of the following: `FR, DE, GB, AT, CH, ES, IT, PL, FI, NL, BE`
2. Don't select CBD as an industry during onboarding

##### Mercado Pago

1. Set your store country to one of the following: `AR, BR, CL, CO, MX, PE, UY`
2. Make sure the help text is shown when setting up the gateway with links to registration and the settings screen.

### WooCommerce Payments

1. Set your store country to one of the following: `US, PR`
2. Don't select CBD as an industry during onboarding
3. Make sure the WC Pay card is shown above the other payment gateways
4. Attempt to install and configured the gateway

##### Cash on Delivery

1. Make sure "Enable" is shown and clicking this enables the gateway
2. Make sure the "Manage" button is shown after enabling the gateway

##### Direct bank transfer

1. Make sure "Set up" is shown next to the gateway
2. Enter some bank details
3. Make sure the gateway is marked as enabled and configured with the entered settings when visiting the legacy settings screen

##### RazorPay

1. Set your store country to India
2. Don't select CBD as an industry during onboarding

##### PayU

1. Set your store country to India
2. Don't select CBD as an industry during onboarding

##### Eway

1. Set your store country to one of the following: `AU, NZ`
2. Don't select CBD as an industry during onboarding
3. Make sure the API key and password fields are shown

##### Square

1. Set your store country to the `US` and select CBD as an industy during onboarding OR set your store country to one of `US, CA, JP, GB, AU, IE`, don't select CBD as an industry and select that you have a physical store in the business details step.
2. Make sure the connection flow is shown.

## 2.4.0

### Add padding on table header button #7213

1. Create tax rates
2. Go to Analytics -> Taxes
3. Check it the table style renders as expected

### Add target to the button to open it in a new tab #7110

1. Navigate to WooCommerce -> Settings -> Payments
2. Wait a few seconds until the Recommended ways to get paid section appears.
3. Click on the See more options button.
4. A new tab/window should open.

### Remote Fee Extensions List #7144

1. Make sure you have Marketplace suggestions turned off `wp option set woocommerce_show_marketplace_suggestions no`
2. Set your store to a US address
3. Be sure to deactivate the following extensions if they are active

-   WooCommerce Payments
-   WooCommerce Services
-   Jetpack
-   Mailpoet
-   Facebook for WooCommerce
-   Google Listings and Ads
-   Mailchimp for WooCommerce
-   Creative Mail

5. Set product types to "Physical" at `/wp-admin/admin.php?page=wc-admin&path=%2Fsetup-wizard&step=product-types`
6. Set the industry to anything other than CBD at `/wp-admin/admin.php?page=wc-admin&path=%2Fsetup-wizard&step=industry`

#### Scenario 1: Default functionality remains

1. Go to Business Details step of the OBW `/wp-admin/admin.php?page=wc-admin&path=%2Fsetup-wizard&step=business-features`
2. Click on "Free features" tab
3. Click the dropdown "Add recommended business features to my site" to see the list of free extensions.
4. See 9 total suggestions

<img width="520" alt="Screen Shot 2021-05-28 at 12 18 57 PM" src="https://user-images.githubusercontent.com/1922453/119912118-ea3ccd00-bfae-11eb-979c-82d2cb85ed4c.png">

#### Scenario 2: Default functionality remains

1. Turn Marketplace Suggestions on `wp option set woocommerce_show_marketplace_suggestions yes`
2. Repeat steps in Scenario 1 and see the same result

#### Scenario 3: CBD industry

1. Select CBD industry only at `/wp-admin/admin.php?page=wc-admin&path=%2Fsetup-wizard&step=industry`
2. Visit the free features of Business Details and see that WooCommerce Payments is no longer being shown
3. Add another industry in addition to CBD and confirm WooCommerce Payments is still not being shown

#### Scenario 4: Downloads Product Type

1. Set the product type to "downloads" only at `/wp-admin/admin.php?page=wc-admin&path=%2Fsetup-wizard&step=product-types`
2. Visit the free features of Business Details and see that WooCommerce Shipping is not shown
3. Add another product type, such as "physical" and confirm WooCommerce Shipping is now being shown

#### Scenario 5: Location

1. Set your store location to South Africa
2. Confirm WooCommerce Payments, WooCommerce Services Shipping, and WooCommerce Services Tax are not shown.

#### Scenario 6: Extension already activated

1. Install and activate Jetpack
2. Confirm Jetpack in not show in Free features list

### Include onboarding settings on the analytic pages #7109

1. Finish the onboarding wizard as usual.
2. Navigate to one of the analytic pages then refresh the page.
3. Navigate to WooCommerce -> Home and start OBW again.
4. Click the continue button.
5. OBW should continue without an error.

### Set target to blank for the external links #6999

1. Clone this repository.
2. Complete the OBW
3. Navigate to WooCommerce -> Home
4. Hide the Setup task list on the Home screen.
5. Note that the Store Management section appears on the Home screen.
6. Click on the Visit My Store link.
7. Observe that the link opens in a new tab.

### Exclude WC Shipping for store that are only offering downloadable products #6917

1. Start OBW and enter an address that is in the US.
2. Choose "food and drink" from the Industry
3. Choose "Downloads" from the Product Types step.
4. When you get to the Business Details step, expand "Add recommended business features to my site" by clicking the down arrow.
5. Note that "WooCommerce Shipping" is not listed.

### Redirect users to WooCommerce Home after setting up a payment method #6891

1. Navigate to WooCommerce -> Home and choose "Choose payment methods".
2. Click one of the payment methods and go through the setup process.
3. You should be redirected to WooCommerce Home with a notice when you are done.

### Disable the continue btn when plugins are being installed/activated #6838

1. In OBW fill out store details with a USA address
2. Click Continue and select Fashion, apparel, and accessories,
3. Click Continue, and select Physical products, and continue.
4. The business details tab should show a Business details tab, and a Free features tab (disabled at first)
5. Select 1-10 for the first dropdown, and No for the second, and click Continue.
6. Make sure the "Add recommended business features to my site is ticked
7. Click Continue, before the page redirects click Continue again
8. Confirm no error has been recorded in your browser console.

### Business features uncheck creative mail by default #7139

2. Complete the OBW until you get to the business details step.
3. Continue setup until the Business Detail step.
4. Open `Free Features` tab and toggle dropdown for `Add recommended business features to my site`.
5. Observe that the list have `Creative Mail` unchecked by default.

### Fix an issue with OBW when wc-pay and Jetpack are both being installed. #6957

-   Complete the OBW until you get to the business details step.
-   Deselect "Add recommended business features to my site", and select only Jetpack and WooCommerce Payments for installation.
-   The plugins should be installed and activated correctly, and you should be able to continue in the flow.

## 2.3.0

### Retain persisted queries when navigating to Homescreen #6614

1. Go to Analytics Report.
2. Change Time period.
3. Navigate to another report.
4. Notice that the time period stays the same.
5. Navigate to Homescreen.
6. Navigate back to previous Analytics Report.
7. Ensure that the time period is _still_ what you set on step 2.

### Set up shipping costs task, redirect to shipping settings after completion. #6791

-   Create a new store, and finish the Onboarding flow
-   Go to **WooCommerce > Home** and select the **Set up shipping costs** task, it should show the standard stepper
-   Type some number in the 'Shipping cost' input
-   Click the 'Rest of the world' toggle to toggle it on.
-   Type some number in the 'Shipping cost' input box under the 'Rest of the world' label
-   Finish the set up, but don't need to install the shipping label plugin
-   Once on home screen the **Set up shipping costs** task should show as finished
-   Click on the task again
-   It should now redirect to the shipping settings page.

### New Google Listings & Ads extension in onboarding #6939

-   On a new WooCommerce site/install
-   Go through all the onboarding steps till we reach `Included business features`
-   Ensure the plugin shows up correctly with the right naming and select it as the only extension to add
    ![image](https://user-images.githubusercontent.com/11388669/117138107-7be75f00-ada2-11eb-9077-c839cee2155e.png)
-   Continue and confirm the `plugins were successfully activated` notice shows up
-   Finish onboarding and confirm that the extension was actually activated

**New Google Listings & Ads extension support in Installed Marketing Extensions section**

-   View the installed marketing extensions section on the page Marketing > Overview
-   The extension should appear in the following states:

1. Extension not installed (should not be included in the list, if the list is empty this section won't show at all)
   ![image](https://user-images.githubusercontent.com/11388669/117135419-d2529e80-ad9e-11eb-9752-081f00beb11c.png)

2. Extension installed but not activated (click activate to confirm it works)
   ![image](https://user-images.githubusercontent.com/11388669/117135541-fb732f00-ad9e-11eb-95f9-d3739fc715da.png)

3. Extension installed and activated but not setup yet (click "Finish setup" to confirm we get redirected to the get started page)
   ![image](https://user-images.githubusercontent.com/11388669/117135812-602e8980-ad9f-11eb-83f8-29889cdb7202.png)

4. Extension installed, activated and setup (click settings to confirm we get redirected to the settings page)
   ![image](https://user-images.githubusercontent.com/11388669/117135999-ab489c80-ad9f-11eb-874b-5f6f5ec4ce5d.png)
   Note: The documentation link is not active yet (still in draft)

### Add plugin installer to allow installation of plugins via URL #6805

1. Visit any admin page with the params `plugin_action` (`install`, `activate`, or `install-activate`) and `plugins` (list of comma separated plugins). `wp-admin/admin.php?page=wc-admin&plugin_action=install&plugins=jetpack`
2. If visiting this URL from a link, make sure you are sent back to the referer.
3. Check that the plugins provided are installed, activated, or both depending on your query.

### Add event recording to start of gateway connections #6801

-   Enable debug messages inside browser devtools, you can do it by running `localStorage.setItem( 'debug', 'wc-admin:*' );` in your browser console. And don't forget to enable all log levels.
-   Create a new store with event tracking enabled.
-   Select `United States` or `UK` as the store country.
-   Visit the Payments task and click to setup `Stripe` and `PayPal`.
-   Verify the event `wcadmin_payments_task_stepper_view` with the right `payment_method was recorded correctly.
-   Press `Proceed` and verify the event `wcadmin_tasklist_payment_connect_start` with the right `payment_method` was recorded.
-   Verify that the event `wcadmin_tasklist_payment_connect_start` also is recorded for the payment gateways: Square, Eway (for AU and NZ) and generic gateways like PayFast (for ZA) and PayStack (for ZA, GH, and NG).

### Add recommended payment methods in payment settings. #6760

-   Create a new store and finish the onboarding flow, making sure your store location is filled out and within US | PR | AU | CA | GB | IE | NZ
-   Visit **Woocommerce > Settings > Payments** you might have to wait a couple seconds, but it should show a card with **Recommended ways to get paid** listing 3 different payment providers (WC Payments, Stripe, and Paypal).
-   Click `Get started` on one of the providers, it will show a loading icon (installing the plugin), once done it should redirect you to the plugin set up page.
-   Check if the plugin is installed and activated.
-   Go back to the payment settings page
-   Notice how the plugin you had previously installed and activated does not display anymore.
-   Go to **WooCommerce > Settings > Advanced > WooCommerce.com** and un-select **Show Suggestions** and save
-   Go to the payments setting screen again, the card should not be displayed.
-   Enable the **Show Suggestions** again in **WooCommerce > Settings > Advanced > WooCommerce.com**
-   Go to the payments setting screen again, the card should be displayed.
-   Click on the 3 dots of the card, click `Hide this`, it should make the card disappear, it should also not show on refresh.
    This can't be shown again unless the `woocommerce_show_marketplace_suggestions` option is deleted (through PHPMyAdmin or using `wp option delete woocommerce_show_marketplace_suggestions`).

### Use the store timezone to make time data requests #6632

1. Go to Settings -> General.
2. Set your store timezone significantly ahead of or behind the timezone you currently reside in.
3. Create a test order and mark complete.
4. Navigate to various analytics reports and note the time filter is based on the current store time. E.g., If your store timezone is 12 hours ahead of your current time, you may see `1st - 23rd` instead of `1st - 22nd` for "Month to date" depending on your time of day.
5. Note that the recently added order shows up in analytics reports.
6. Change your timezone and repeat, testing with both locations (e.g., `Amsterdam`) and also UTC offsets (e.g., `UTC-6`).

### Update the checked input radio button margin style #6701

1. Go to Home.
2. Click on 'Add my products'.
3. Select 'Start with a template'.
4. Click on the input radio button and see that render as expected.

### Fix varation bug with Products reports #6647

1. Add two variable products. You want to have at least one variable for each product.

Product A - color:black
Product A - color:white

Product B - size:small
Product B - size:medium

2. Make an order for each product.
3. Navigate to Analytics -> Products
4. Choose 'Single product' from the 'Show' dropdown and search for the product.
5. Confirm that the "Variations" table shows the correct variations. If you searched for the 'Product A', then you should see color:black and color:white.

In case the report shows "no data", please reimport historical data by following the guide on [here](https://docs.woocommerce.com/document/woocommerce-analytics/#analytics-settings__import-historical-data)

### Check active plugins before getting the PayPal onboarding status #6625

-   Go to the WooCommerce home page
-   Open your browser console
-   Choose payment methods
-   See no error message

### Remove PayPal for India #6828

-   Setup a new store and set your country to `India`.
-   Go to 'Choose Payment method' Checklist on the home page.
-   Verify that PayPal is not presented as a payment method.

### Refactor payments to allow management of methods #6786

1. Do not select "CBD industry" as a store industry during onboarding.
2. Make various payment methods visible by switching to different countries.
3. Attempt to set up various payment methods.
4. Make sure that after setup, a `Manage` link is shown that links to the payment method settings page.
5. Check that simple methods like, cash delivery or bank transfer initially have an `Enable` option.

### Update WC Payments plugin copy #6734

1. Install WooCommerce with WooCommerce Payments
2. Clone this branch and run npm start (only needed if you are using dev version)
3. Navigate to WooCommerce -> Home and observe the copy change.

## 2.2.0

### Fixed event tracking for merchant email notes #6616

-   Create a brand new site.
-   Install a plugin to log every sent email (you can use [WP mail logging](https://wordpress.org/plugins/wp-mail-logging/)).
-   Install and activate [WC Admin Test Helper](https://github.com/woocommerce/woocommerce-admin-test-helper/releases).
-   Create a [new note type `email`](https://github.com/woocommerce/woocommerce-admin-test-helper/wiki/Admin-notifications#3---email).
-   Now go to WooCommerce > Settings > Email (`/wp-admin/admin.php?page=wc-settings&tab=email`) and check the checkbox `Enable email insights` and save changes.
-   Now run the [`wc_admin_daily` cron](https://github.com/woocommerce/woocommerce-admin-test-helper/wiki/Tools) manually.
-   Go to Tools > WP Mail Logging Log (`/wp-admin/tools.php?page=wpml_plugin_log`) and verify the testing email note was sent.
-   View the message and press `Test action` (a broken image will be visible under the button, but that's expected and only visible in a test environment).

### Payments task: include Mercado Pago #6572

-   Create a brand new store.
-   Set one of the following countries in the first OBW step:

```
Mexico
Brazil
Argentina
Chile
Colombia
Peru
Uruguay
```

-   Continue with the OBW and finish it up.
-   Select `Choose payment methods` in the setup task list (`Get ready to start selling`).
-   Press the `Setup` button in the `Mercado Pago Payments` box.
-   Try the links presented after the plugin's installation and verify they are working.
-   Confirm that the `Mercado Pago payments for WooCommerce` plugin was installed.
-   Press `Continue`.
-   Now the `Mercado Pago Payments` option should appear as active.

### Update contrast and hover / active colors for analytics dropdown buttons #6504

1. Go to analytics.
2. Verifty the dropdown buttons (date range or filters) are now higher contrast.
3. Verifty the text and chevron in the dropdown button turn blue on hover, and while active.

### Set default value to array when op is `contains` #6622

1. Clone and start https://github.com/Automattic/woocommerce.com
2. Open `notifications.json.php` from woocommerce.com repository and find a rule that uses the `contains` operator and remove the `default` key. Please make a note of the option name.
3. Open `src/RemoteInboxNotifications/DataSourcepoller.php` from your WooCommerce Admin repository and change the datasource to your local woocommerce.com (woocommerce.test)
4. Make sure your local WooCommerce Admin database does not have the option from step #2
5. Install and activate [WC Admin Test Helper](https://github.com/woocommerce/woocommerce-admin-test-helper/wiki/Admin-notifications).
6. Now run the [`wc_admin_daily` cron](https://github.com/woocommerce/woocommerce-admin-test-helper/wiki/Tools) manually.
7. Check your debug log in `wp-content/debug.log`. You should see PHP error.

### Close activity panel tabs by default and track #6566

1. Open your browser console and enter `localStorage.setItem( 'debug', 'wc-admin:tracks' );`. Make sure the "Verbose" is selected under the levels shown.
2. With the task list enabled, navigate to the homescreen.
3. Check that the `wcadmin_activity_panel_visible_panels` event is shown with `taskList: true` in its data.
4. Hide the task list.
5. Note that `wcadmin_activity_panel_visible_panels` event is shown with visible activity panels.
6. After refreshing, make sure that the "Orders" activity panel is closed by default.

### Update undefined task name properties for help panel tracks #6565

1. Enter `localStorage.setItem( 'debug', 'wc-admin:*' );` into your console. Leave your console open.
2. Navigate to the homescreen.
3. Open the "Help" tab in the top right.
4. Note the tracks information in the console includes `homescreen` for the `taskName` property.
5. Click on a help item.
6. Note `homescreen` is used for the `taskName` in the help panel click tracks event.
7. Navigate to any task in the task list.
8. Click on the "Help" tab.
9. Note the `taskName` for the event is the current task.
10. Click on a help item.
11. Note the `taskName` for the event is the current task.

### Add gross sales column to CSV export #6567

1. Navigate to Analytics -> Revenue
2. Adjust the date filter so that more than 25 rows are visible
3. Click "Download"
4. Click the download link in the email
5. See gross sales column

### Add customer name column to CSV export #6556

-   Create more than 25 orders
-   Go to Analytics -> Orders -> Click "Download"
-   Click download link in the email
-   See customer column with customer full name

### Allow the manager role to query certain options #6577

Testing `woocommerce_ces_tracks_queue`

1. Checkout this branch.
2. Open browser inspector and select the Network tab.
3. Navigate to WooCommerce -> Settings.
4. Confirm that the request to `/wp-json/wc-admin/options?options=woocommerce_ces_tracks_queue&_locale=user` returns 200 status.

Testing `woocommerce_navigation_intro_modal_dismissed`

1. Checkout this branch.
2. Navigate to WooCommerce -> Settings -> Advanced -> features (/wp-admin/admin.php?page=wc-settings&tab=advanced&section=features) and enable Navigation
3. Open browser inspector and select the Network tab.
4. Navigate to WooCommerce -> Home
5. Confirm that the request to `/wp-json/wc-admin/options?options=woocommerce_navigation_intro_modal_dismissed&_locale=user` returns 200 status.

### Refactor profile wizard benefits step and add tests #6583

1. Deactivate Jetpack and/or WooCommerce Services.
2. Visit the profiler benefits page. `/wp-admin/admin.php?page=wc-admin&path=%2Fsetup-wizard&step=benefits`
3. Click "Yes please!" to continue.
4. Without connecting to Jetpack, navigate backwards using your browser's back button.
5. Make sure the page continues to display (benefits may have changed) and that action buttons are functional.
6. Make sure skipping the install works as expected.
7. Connect Jetpack.
8. Attempt to directly visit the benefits page. `/wp-admin/admin.php?page=wc-admin&path=%2Fsetup-wizard&step=benefits`
9. Note that you are redirected to the homescreen.

### Delete customer data on network user deletion #6574

1. Set up a multisite network.
2. Create a new user.
3. Make an order with that user.
4. Note the customer data under WooCommerce -> Customers.
5. Navigate to Network -> All users `/wp-admin/network/users.php`.
6. Delete that user.
7. Wait for the scheduled action to finish or manually run the `wc-admin_delete_user_customers` action under Tools -> Scheduled Actions.
8. Navigate to WooCommerce -> Customers.
9. Make sure that customer data has been deleted.

### Fix "Themes" step visibility in IE 11 #6578

1. Get an IE 11 test environment. I downloaded a trial version of Parallels Desktop on [here](https://www.parallels.com/) and IE 11 virtual machine from [developer.microsoft.com](https://developer.microsoft.com/en-us/microsoft-edge/tools/vms/)
2. Make a zip version of this branch by running `npm run test:zip`
3. Make a JN site -> install and activate the zip file.
4. Open IE 11 and start OBW
5. Confirm that the themes are displayed correctly.

### Fix hidden menu title on smaller screens #6562

1. Enable the new navigation.
2. Shorten your viewport height so that the secondary menu overlaps the main.
3. Make sure the menu title can still be seen.

### Add filter to profile wizard steps #6564

1. Add the following JS to your admin head. You can use a plugin like "Add Admin Javascript" to do this:

```
wp.hooks.addFilter( 'woocommerce_admin_profile_wizard_steps', 'woocommerce-admin', ( steps ) => {
	return steps.filter( ( step ) => step.key !== 'product-types' );
} );
```

2. Navigate to the profile wizard. `wp-admin/admin.php?page=wc-admin&path=%2Fsetup-wizard`.
3. Make sure the filtered step (product types) is not shown.

### Adjust targeting store age: 2 - 5 days for the Add First Product note #6554

-   Checkout this branch.
-   Create a zip for testing with `npm run zip:test`.
-   Create a `jurassic.ninja` instance.
-   Upload the plugin and activate it.
-   Update the installation date (we need a store between 2 and 5 days old). You can do it with an SQL statement like this:

### Update Insight inbox message #6555

1. Checkout this branch.
2. Update the installation date of your store if it hasn't been at least a day. You can use the following SQL uqery.

```
UPDATE `wp_options` SET `option_value`=UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 5 day)) WHERE `option_name` = 'woocommerce_admin_install_timestamp';
```

-   Install and activate [WC Admin Test Helper](https://github.com/woocommerce/woocommerce-admin-test-helper/releases).
-   Now run the [`wc_admin_daily` cron](https://github.com/woocommerce/woocommerce-admin-test-helper/wiki/Tools) manually.
-   You should have received an email like the image above.
-   Verify the note's status is `sent`. You can use an SQL statement like this:

```
SELECT `status` FROM `wp_wc_admin_notes` WHERE `name` = 'wc-admin-add-first-product-note'
```

-   Now delete the note with an SQL statement like:

```
DELETE FROM `wp_wc_admin_notes` WHERE `name` = 'wc-admin-add-first-product-note';
```

-   Add a new order and run the cron.
-   No note should have been added.
-   Remove the order, add a product and run the cron.
-   No note should have been added.
-   Delete the product and modify the store creation date to 7 days with an SQL statement like:

```
UPDATE `wp_options` SET `option_value`=UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 7 day)) WHERE `option_name` = 'woocommerce_admin_install_timestamp';
```

-   No note should have been added.

### Improve WC Shipping & Tax logic #6547

**Scenario 1** - Exclude the WooCommerce Shipping mention if the user is not in the US

1. Start OBW and enter an address that is not in the US
2. Choose "food and drink" from the Industry (this forces Business Details to display "Free features" tab)
3. When you get to the "Business Details", click "Free features"
4. Expand "Add recommended business features to my site" by clicking the down arrow.
5. Confirm that "WooCommerce Shipping" is not listed

**Scenario 2**- Exclude the WooCommerce Shipping mention if the user is in the US but only selected digital products in the Product Types step

1. Start OBW and enter an address that is in the US.
2. Choose "food and drink" from the Industry (this forces Business Details to display the "Free features" tab)
3. Choose "Downloads" from the Product Types step.
4. When you get to the Business Details step, expand "Add recommended business features to my site" by clicking the down arrow.
5. Confirm that "WooCommerce Shipping" is not listed

**Scenario 3** - Include WooCommerce Tax if the user is in one of the following countries: US | FR | GB | DE | CA | PL | AU | GR | BE | PT | DK | SE

1. Start OBW and enter an address that is in one of the following countries

    US | FR | GB | DE | CA | PL | AU | GR | BE | PT | DK | SE

2. Continue to the Business Details step.
3. Expand "Add recommended business features to my site" by clicking the down arrow.
4. Confirm that "WooCommerce Tax" is listed.
5. Install and activate [WC Admin Test Helper](https://github.com/woocommerce/woocommerce-admin-test-helper/wiki/Admin-notifications).
6. Run the [`wc_admin_daily` cron](https://github.com/woocommerce/woocommerce-admin-test-helper/wiki/Tools).
7. Navigate to WooCommerce -> Home and confirm the Insight note.

### Use wc filter to get status tabs for tools category #6525

1. Register a new tab via the filter.

```
add_filter( 'woocommerce_admin_status_tabs', function ( array $tabs ) {
	$tabs['my-tools-page'] = __( 'My Tools Page', 'your-text-domain' );
	return $tabs;
} );
```

2. Enable the new navigation.
3. Make sure the menu item for the registered tab is shown under `Tools`.

### Remove mobile activity panel toggle #6539

1. Narrow your viewport to < 782px.
2. Navigate to various WooCommerce pages.
3. Make sure the various tabs can be seen and function as expected.
4. Navigate to a WooCommerce Admin page that is not the homepage.
5. Open the "Inbox" panel.
6. Click on the "Inbox" panel button again.
7. Make sure the panel closes as expected and does not reopen immediately.

### Add legacy report items to new navigation #6507

1. Enable the new navigation experience.
2. Navigate to Analytics->Reports.
3. Note that all the reports exist and navigating to those reports works as expected.
4. Check that report menu items are marked active when navigating to that page.

### Add navigation container tests #6464

1. On a new site, finish the store setup wizard, but don't hide the task list.
2. Navigate to a WooCommerce Admin Analytics page.
3. Note the menu is under the "Analytics" level.
4. Click the "Store Setup" link in the top right hand corner.
5. Note that the navigation level automatically is updated to the root level where the "Home" item is marked active.

### Add preview site button on the appearance task #6457

1. Navigate to Home and click "Personalzie your store" task.
2. Click on the "Preview Site" button on the header.
3. A new tab should open and the URL should be the site URL.
4. Navigate to other tasks such as "Store Details" or "Add products" .
5. The "Preview Site" should not be shown on the other tasks.

### Store profiler - Added MailPoet to new Business Details step #6515

-   Create a brand new site and go to the OBW.
-   In the first OBW step (`Store Details`) set `US` in the `Country / Region` selector.
-   Continue with the profiler.
-   In the 4th step (`Business Details`) choose any of the options in both selectors.
-   Under `Free features` tab, verify that the displayed extensions are:

```
MailPoet
Facebook
Google Ads
Mailchimp
Creative Mail
```

(In that order)

-   Verify that the Creative Mail option copy is `Emails made easy with Creative Mail`.

### Store profiler - Added MailPoet to Business Details step #6503

-   Create a brand new site and go to the OBW.
-   In the first OBW step (`Store Details`) set a Country / Region other than `US | BR | FR | ID | GB | DE | VN | CA | PL | MY | AU | NG | GR | BE | PT | DK | SE | JP` (e.g.: Uruguay).
-   Continue with the profiler.
-   In the 4th step (`Business Details`) choose any of the options in both selectors.
-   Verify that the displayed extensions are:

```
MailPoet
Facebook
Google Ads
Mailchimp
Creative Mail
```

(In that order)

-   Verify that the Creative Mail option is toggled off by default

### Fix double prefixing of navigation URLs #6460

1. Register a navigation menu item with a full URL or admin link.

```
	\Automattic\WooCommerce\Admin\Features\Navigation\Menu::add_plugin_item(
		array(
			'id'         => 'my-page,
			'title'      => 'My Page,
			'capability' => 'manage_woocommerce',
			'url'        => admin_url( 'my-page '),
		)
	);
```

2. Enable the navigation.
3. Check that the menu item is marked active when visiting that page.
4. Make sure old menu items are still correctly marked active.

### Fix summary number style regression on analytics reports #5913

-   Go to Analytics
-   See that the active (selected) tab is white, with a highlight above the tab.
-   See that inactive tabs are a lighter shade of grey.

### Update payment card style on mobile #6413

-   Using a small size screen, go to your WooCommerce -> Home -> Choose payment methods.
-   See that the text descriptions for payment methods have a margin between them and the edge of the screen.

### Navigation: Correct error thrown when enabling #6462

1. Create a fresh store
2. Navigate to WooCommerce -> Settings -> Advanced Tab -> Features
3. Check the box to add the new navigation feature, and hit save
4. Ensure that the new navigation appears on the left as expected

### Remove Mollie promo note on install #6510

-   If you do not currently have the Mollie note on your WooCommerce Admin home screen, you can add a test note with the correct name as follows:
    1. Install the WooCommerce Admin Test Helper plugin [here](https://github.com/woocommerce/woocommerce-admin-test-helper)
    2. Go to the [Admin notes tab](https://github.com/woocommerce/woocommerce-admin-test-helper/wiki/Admin-notifications)
    3. Add an admin note with the name `wc-admin-effortless-payments-by-mollie`
    4. Go to the WCA home screen and verify that your test note is present
-   The note is removed on a new version install, so either install an old version of WCA and upgrade to the current one, or trigger the install process manually:
    1. Install the WCA test helper
    2. Go to the Tools tab
    3. Click the `Trigger WCA install` button

### Deprecate Onboarding::has_woocommerce_support #6401

-   Clear existing site transients. For example, by using the [Transients Manager](https://wordpress.org/plugins/transients-manager/) plugin, and pressing the "Delete all transients" button it provides.
-   Add any new theme to WordPress but **DO NOT** activate it.
-   Initialize the Onboarding Wizard.
-   See that the Themes step loads fast 😎
-   See that the new theme is listed in the Themes step.

### Set up tasks can now navigate back to the home screen #6397

1. With a fresh install of wc-admin and woocommerce, go to the home screen
2. Going to the homescreen redirects to the profile setup wizard, click "Skip setup store details" to return to the home screen
3. On the home screen you will see the setup task list. It has the heading "Get ready to start selling"

For each task in that list apart from "Store details":

1. Click the item
2. You should land on the setup task page
3. A title in the top left should reflect the original task name from the task list. e.g. "Add tax rates"
4. Clicking the chevron to the left of the title should take you back to the home screen

### Add Ireland to Square payment method #6559

1. Go to the store setup wizard `/wp-admin/admin.php?page=wc-admin&path=%2Fsetup-wizard`
1. Set up your store with Ireland as its country, and proceed until the `Business Details` step
1. In "Currently selling anywhere?" dropdown, select either:
    - Yes, in person at physical stores and/or events
    - Yes, on another platform and in person at physical stores and/or events
1. Finish the setup wizard, and go to payments task `/wp-admin/admin.php?page=wc-admin&task=payments`
1. Observe Square as a payment method option

### Add CES survey for search product, order, customer #6420

-   Make sure tracking is enabled in settings.
-   Delete the option `woocommerce_ces_shown_for_actions` to make sure CES prompt triggers when updating settings.
-   Enable the logging of Tracks events to your browser dev console `localStorage.setItem( 'debug', 'wc-admin:tracks' );`

**Testing search on products:**

-   Go to Products > All Products.
-   Type in anything in search bar, click on "Search products".
-   Observe CES prompt "How easy was it to use search?" is displayed.

**Testing search on orders:**

-   Go to Orders > Orders.
-   Type in anything in search bar, click on "Search orders".
-   Observe CES prompt "How easy was it to use search?" is displayed.

**Testing search on customers:**

-   Go to Customers.
-   Type in anything in search bar, and press enter.
-   Observe CES prompt "How easy was it to use search?" is displayed

### Add CES survey for importing products #6419

-   Make sure tracking is enabled in settings.
-   Delete the option `woocommerce_ces_shown_for_actions` to make sure CES prompt triggers when updating settings.
-   Enable the logging of Tracks events to your browser dev console `localStorage.setItem( 'debug', 'wc-admin:tracks' );`
-   If you don't have a product CSV export, you can obtain a sample CSV [here](https://gist.githubusercontent.com/ilyasfoo/507f9579531cf4bf50fe4c0e9c48a23d/raw/05e47e6731471464c757e893c3f2d8a9b89453c0/product-export.csv).
-   Go to Products > All Products.
-   Click on "Import".
-   Upload CSV file and finish the import process.
-   Observe CES prompt "How easy was it to import products?" is displayed.

### Add CES survey for adding product categories and tags #6418

-   Make sure tracking is enabled in settings.
-   Delete the option `woocommerce_ces_shown_for_actions` to make sure CES prompt triggers when updating settings.
-   Enable the logging of Tracks events to your browser dev console `localStorage.setItem( 'debug', 'wc-admin:tracks' );`

**Testing product categories:**

-   Go to Products > Categories.
-   Add a new category.
-   Observe CES prompt "How easy was it to add a product category?" is displayed.

**Testing product tags:**

-   Go to Products > Tags.
-   Add a new tag.
-   Observe CES prompt "How easy was it to add a product tag?" is displayed.

**Testing product attributes:**

-   Go to Products > Attributes.
-   Add a new attribute.
-   Observe CES prompt "How easy was it to add a product attribute?" is displayed.

### Add paystack as payment option for African countries #6579

1. Go to the store setup wizard `/wp-admin/admin.php?page=wc-admin&path=%2Fsetup-wizard`
2. Set up your store with **South Africa - Eastern Cape** as its country, and proceed
3. Select **Fashion, apparel, and accessories** as the industry, and finish the rest of the onboarding flow.
4. Once finished it will redirect to the home screen, click on the **Choose payment methods** task
5. Both **Paystack** and **Payfast** should be listed above **Paypal**
6. Click **Set up** on the Paystack payment option
7. It should successfully finish the **Install** step, and ask for a public and secret key.
8. Check if the **Paystack account** link, directs you to the **dashboard.paystack.com/#/settings/developer**
9. Set `public_key` for public key, and `secret_key` for secret key and click **Proceed**
10. It should redirect you to the payment list and the **Set up** button should be gone, and replaced by an enabled toggle button.
11. You should be able to successfully toggle paystack from on to off and back. Leave it selected for now.
12. Go to **WooCommerce > Settings > Payments**, **Paystack** should be selected.
13. Click **Manage**, the secret and public key's should match what you entered in step 9.

# 2.1.3

### Fix a bug where the JetPack connection flow would not activate #6521

1. With a fresh install of wc-admin and woocommerce, go to the home screen
2. Going to the homescreen redirects to the profile setup wizard
3. The first step is "Store details" choose United States (any state) for country and fill in the other details with test data.
4. Click "continue", you should be taken to the "Industry" step.
5. In the "Industry" step check the "Food and Drink" option only. Click "continue"
6. In the "Product Type" step choose any value and click "continue"
7. You should arrive at the "Business details" step which provides 2 tabs: "Business details" and "Free features". In the "Business Details" tab fill out the dropdowns with any values. Click "continue".
8. In the "Free features" step expand the list of extensions to install by clicking the arrow to the right of "Add recommended business features to my site".
9. Uncheck all the extensions except for "Enhance speed and security with Jetpack"
10. Click "continue", the plugin will be installed and you should arrive at the theme step.
11. Click "Continue with my active theme"
12. After finishing the wizard, this should redirect you to the "Jetpack" setup connection flow. (You should not be redirected straight to the homescreen).

### Update target audience of business feature step #6508

Scenario #1

1. With a fresh install of wc-admin and woocommerce, go to the home screen, which starts the onboarding wizard
2. Fill out the store details with a canadian address (addr: 4428 Blanshard, country/region: Canada -- British Columbia, city: Victoria, postcode: V8W 2H9)
3. Click continue and select **Fashion, apparel, and accessories**, continue, and select **Physical products**, and continue.
4. The business details tab should show a **Business details** tab, and a **Free features** tab (disabled at first)
    - There should only be dropdowns visible on the **Business details** step (no checkboxes)
5. Select **1-10** for the first dropdown, and **No** for the second, and click Continue.
6. Click on the expansion icon for the **Add recommended business features to my site**
7. It should list 7 features, including **WooCommerce Payments** (top one)
    - Note down the selected features, for step 10
8. Click continue, and select your theme, after it should redirect to the home screen (showing the welcome modal, you can step through this).
9. The home screen task list should include a **Set up WooCommerce Payments** task, and there should also be a **Set up additional payment providers** inbox card displayed (below the task list).
10. Go to **Plugins > installed Plugins**, check if the selected plugin features selected in step 7 are installed and activated.

Scenario #2

1. With a fresh install of wc-admin and woocommerce, go to the home screen, which starts the onboarding wizard
2. Fill out the store details with a spanish address (addr: C/ Benito Guinea 52, country/region: Spain -- Barcelona, city: Canet de Mar, postcode: 08360)
3. Click continue and select **Fashion, apparel, and accessories**, continue, and select **Physical products**, and continue.
4. On the business details tab select **1-10** for the first dropdown, and **No** for the second.
    - After filling the dropdowns it should show several checkboxes with plugins (Facebook, mailchimp, creative mail, google ads)
    - Note which ones you kept selected (you can unselect one or two)
5. Click continue, and select your theme, it should show the **WooCommerce Shipping & Tax** step after, you can click **No thanks**.
6. You will be redirected to the home screen, showing the welcome modal, you can step through this.
7. The task list should show the **Choose payment methods** task, and the **Set up additional payment providers** inbox card should not be present.
8. Click on the **Choose payment methods** task, it should not be displaying the **Woocommerce Payments** option.
9. Go to **Plugins > installed Plugins**, check if the selected plugin features selected in step 4 are installed and activated.

### Improve AddFirstProduct email note contents #6617

-   Install the plugin in a fresh site.
-   Make sure the store has 0 products and 0 orders.
-   Update the installation date (we need a store between 2 and 5 days old). You can do it with an SQL statement like this:

```
UPDATE `wp_options` SET `option_value`=UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 4 day)) WHERE `option_name` = 'woocommerce_admin_install_timestamp';
```

-   Install and activate [WC Admin Test Helper](https://github.com/woocommerce/woocommerce-admin-test-helper/wiki/Admin-notifications).
-   Make sure the `woocommerce_merchant_email_notifications` option is set to `yes` by [using the update option tool](https://github.com/woocommerce/woocommerce-admin-test-helper/wiki/Update-option).

-   Run the [`wc_admin_daily` cron](https://github.com/woocommerce/woocommerce-admin-test-helper/wiki/Tools).
-   You should have received an email like the image above.

## 2.1.2

### Add Guards to "Deactivate Plugin" Note Handlers #6532

#### Test incompatible WooCommerce version

-   Install and activate Woocommerce 4.7
-   See that the Woocommerce Admin plugin is deactivated.
-   Add the Deactivate Plugin note via SQL.

```
INSERT INTO `wp_wc_admin_notes` (`name`, `type`, `locale`, `title`, `content`, `content_data`, `status`, `source`, `date_created`, `date_reminder`, `is_snoozable`, `layout`, `image`, `is_deleted`, `icon`) VALUES ( 'wc-admin-deactivate-plugin', 'info', 'en_US', 'Deactivate old WooCommerce Admin version', 'Your current version of WooCommerce Admin is outdated and a newer version is included with WooCommerce.  We recommend deactivating the plugin and using the stable version included with WooCommerce.', '{}', 'unactioned', 'woocommerce-admin', '2021-03-08 01:26:44', NULL, 0, 'plain', '', 0, 'info');
```

-   See that the note is in the inbox
-   Activate the Woocommerce Admin plugin.
-   See that Woocommerce Admin immediately de-activates without a fatal error.
-   See that the note remains in inbox

#### Test compatible WooCommerce version

-   Deactivate the Woocommerce Admin plugin.
-   Install and activate the latest Woocommerce version.
-   Add the Deactivate Plugin note via SQL.

```
INSERT INTO `wp_wc_admin_notes` (`name`, `type`, `locale`, `title`, `content`, `content_data`, `status`, `source`, `date_created`, `date_reminder`, `is_snoozable`, `layout`, `image`, `is_deleted`, `icon`) VALUES ( 'wc-admin-deactivate-plugin', 'info', 'en_US', 'Deactivate old WooCommerce Admin version', 'Your current version of WooCommerce Admin is outdated and a newer version is included with WooCommerce.  We recommend deactivating the plugin and using the stable version included with WooCommerce.', '{}', 'unactioned', 'woocommerce-admin', '2021-03-08 01:26:44', NULL, 0, 'plain', '', 0, 'info');
```

-   Activate the Woocommerce Admin plugin.
-   See that note is **not** in the inbox
-   Add the Deactivate Plugin note via SQL.

```
INSERT INTO `wp_wc_admin_notes` (`name`, `type`, `locale`, `title`, `content`, `content_data`, `status`, `source`, `date_created`, `date_reminder`, `is_snoozable`, `layout`, `image`, `is_deleted`, `icon`) VALUES ( 'wc-admin-deactivate-plugin', 'info', 'en_US', 'Deactivate old WooCommerce Admin version', 'Your current version of WooCommerce Admin is outdated and a newer version is included with WooCommerce.  We recommend deactivating the plugin and using the stable version included with WooCommerce.', '{}', 'unactioned', 'woocommerce-admin', '2021-03-08 01:26:44', NULL, 0, 'plain', '', 0, 'info');
```

-   De-activate the Woocommerce Admin plugin.
-   See that note is **not** in the inbox

## 2.1.0

### Correct the Klarna slug #6440

1. Set up a new store with a UK address so that Klarna available as a payment processor
2. Go to the "Choose payment methods" task item
3. Set up Klarna. The plugin will install.
4. Click Continue. It should take you back to the payment methods page - previously it wasn't doing anything but a console error was displayed.

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
-   Update the installation date (we need a 10-day old store). You can do it with an SQL statement like this (using the WP phpMyAdmin plugin):

```
UPDATE `wp_options` SET `option_value`=UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 10 day)) WHERE `option_name` = 'woocommerce_admin_install_timestamp';
```

-   Confirm that `woocommerce_merchant_email_notifications` was not set before by `core`.
-   Install and activate [WC Admin Test Helper](https://github.com/woocommerce/woocommerce-admin-test-helper/wiki/Admin-notifications).
-   Delete `woocommerce_merchant_email_notifications` if present [using the update option tool](https://github.com/woocommerce/woocommerce-admin-test-helper/wiki/Update-option).

-   Run the [`wc_admin_daily` cron](https://github.com/woocommerce/woocommerce-admin-test-helper/wiki/Tools).
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

### Fixed associated Order Number for refunds #6428

1. In a store with refunded orders.
2. Go to `Analytics` > `Orders`
3. Set the `Date Range` filter in order to cover the refunded order date.
4. Verify that now the associated order number and the related products are visible.

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
2. Install and activate [WC Admin Test Helper](https://github.com/woocommerce/woocommerce-admin-test-helper/wiki/Admin-notifications).
3. Add a new download product.
4. Run the [`wc_admin_daily` cron](https://github.com/woocommerce/woocommerce-admin-test-helper/wiki/Tools) manually.
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
-   Install and activate [WC Admin Test Helper](https://github.com/woocommerce/woocommerce-admin-test-helper/wiki/Admin-notifications)
-   Run the [`wc_admin_daily` cron](https://github.com/woocommerce/woocommerce-admin-test-helper/wiki/Tools) manually
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
