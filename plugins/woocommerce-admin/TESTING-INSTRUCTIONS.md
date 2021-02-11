Testing instructions
====================

## Unreleased

## 2.0.0

### Add the Mollie payment provider setup task #6257

- You'll need a site that has the setup task list visible. Complete the OBW and make sure you're in a Mollie supported country (Such as United Kingdom).
- Go to the setup payments task
- Mollie should be listed as an option
- Click "Set up" button on the Mollie task
- It should install and activate the mollie payments plugin
- The connect step should provide links to create an account or go straight to Mollie settings. (test both links work)
- Click "continue"
- You should arrive back at the payment provider list

### Fix: allow for more terms to be shown for product attributes in the Analytics orders report. #5868

1. Create a product attribute
2. Give the attribute more than 10 terms
3. Go to Analytics -> Orders
4. Add an attribute filter to the list, choose your attribute.
5. Go to the second input field and click on it, a result set of all your terms should appear

### Add: new inbox message - Getting started in Ecommerce - watch this webinar. #6086

- First you'll need to make sure you meet the criteria for the note:
    1. obw is done
    2. revenue is between 0-2500
    3. do not check "setting up for client" in obw
    4. store should have no products
- Next you need to install WP Crontrol, go to its list of cron events and click "run now" on "wc_admin_daily"
- Confirm the new note is displayed and that the content matches that specified below:
    - Title: Getting Started in eCommerce - webinar
    - Copy: We want to make eCommerce and this process of getting started as easy as possible for you. Watch this webinar to get tips on how to have our store up and running in a breeze.
    - CTA leads to: https://youtu.be/V_2XtCOyZ7o
    - CTA label: Watch the webinar

### Update: store deprecation welcome modal support doc link #6094

- Starting with a fresh store (or by deleting the woocommerce_task_list_welcome_modal_dismissed option), visit /wp-admin/admin.php?page=wc-admin. You should see the standard welcome modal.
- Add &from-calypso to the URL. You should see the Calypso welcome modal.
- Notice "Learn more" links to https://wordpress.com/support/new-woocommerce-experience-on-wordpress-dot-com/

### Enhancement: Allowing users to create products by selecting a template. #5892

- Load new store and finish the Wizard
- Go to the `Add my products` task
- Click the `Start with a template` option, and select either a physical, digital, variable product
- Once you click `Go`, it should redirect you to an edit page of the new post, with the data from the sample-data.csv (mentioned in the original ticket). Only the title is missing, as it is saved as auto-draft.
- You should be able to save the new product as draft or publish it.
- You should be able to exit without making any changes, and not having the product show up as draft in your product list. 
  - Create new product from template
  - Wait until redirected
  - Without saving go to the **Products > all products** page, the new product should not be displayed.

### Update: Homescreen layout, moving Inbox panel for better interaction. #6122

- Create a new woo store, and finish the onboarding wizard.
- Go to the home screen, and make sure the panels follow this order:
- Two column:
  - Left column: store setup and/or management tasks + inbox panel
  - Right column: stats overview + store management shortcuts (only shows when setup tasks is hidden)
- Single column:
  - store setup tasks, inbox panel, stats overview, store management links (only visible when setup tasks is hidden).
- Hide the setup tasks list, and see if the store management links show up in the right place.

### Enhancement: Use the new Paypal payments plugin for onboarding. #6261

- Create new woo store, and finish the onboarding wizard
- Go to the home screen, and click the **Set up payments** task. **Paypal Payments** option should be listed as an option, with a **Set up** button.
- Click **Set up** on the Paypal plugin.
- It should automatically start the **Install** step, and install and enable the [Paypal Payments](https://woocommerce.com/products/woocommerce-paypal-payments/) plugin.
- For Paypal Payments version greater then `1.1.0`.
  - For the second step it should show a `Connect` button
  - Click on **Connect** and a window should popup for Paypal, follow this until finished. The last button is - Go back to Woocommerce Developers
  - Once done, the page should reload, and briefly show the setup screen again, it should then finish and go back to the payment list.
  - Once on the payment list, the `Set up` button should be gone, and instead show a toggle, that is set to enabled.
    - The enable/disable button should be correctly reflected in the Woocommerce payment settings screen as well.
- For Paypal Payments version `1.1.0` and below
  - For the second step it will show the manual fields (merchant email, merchant id, client id, client secret). 
  - Check if the help links work below, they should help with finding the above credentials.
    - If you have a business account set up, you can find the credentials in these two places
    - [Get live app credentials](https://developer.paypal.com/developer/applications/)
    - [Merchant id](https://www.paypal.com/businessmanage/account/aboutBusiness)
  - Fill in the credentials and click **Proceed**, this should succeed and redirect you to the Payment options list
  - The **Set up** button should be replaced by a toggle, that is set to enabled.
    - The enable/disable button should be correctly reflected in the Woocommerce payment settings screen as well.

Once everything is configured and enabled do a client test
- Make sure you have added a product and store homescreen (Finish the **Personalize my store** task)
- Navigate to one of the products and add it to the cart
- Click **go to checkout**
- Paypal should be one of the options to pay
- Filling in your billing/shipping info then click pay with **Paypal**
- The paypal pay window should pop up correctly without errors.