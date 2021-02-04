Testing instructions
====================

## 2.0.0

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
