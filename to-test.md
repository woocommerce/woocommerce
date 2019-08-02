### WooCommerce 3.7

### Product Blocks

WooCommerce Product Blocks 2.3 is included in this release. WooCommerce Products Blocks is our eCommerce focused blocks for the Gutenberg editor that has been part of WordPress since 5.0.

The first Product Blocks were included in WooCommerce 3.6 and with the inclusion of Product Blocks 2.3 in WooCommerce 3.7, there are several new features added:

- A new Focal Point picker on the Featured Product block.
- Searching for products in Featured Product & Hand-picked Product blocks is faster.
- A new Product Categories List block.
- Better block branding for easier discoverability.
- A new Featured Category Block; feature a category and show a link to its archive.
- A new Products by Tag(s) block.

In order to have access to the new Product Blocks, you will need to have WordPress 5.1+ installed or have the latest version of the Gutenberg Editor plugin installed.

To test, you'll want to add a new page or post and add each of the new blocks to that page. Some areas to focus on:

- Blocks discoverability
- Add, edit, publish, and delete block
- Block customization

### Additional enhancements

In addition to the above, we have also included the following user-facing enhancements in WooCommerce 3.7.

- The ability to change the “Thanks” wording in emails from the email settings.
- Added new Coupon code generator functionality to the coupons page.
- If variations are missing prices, show a notice in the product data panel.
- Show the quantity refunded on customer facing order screens.
- CSV product import now allows true/false values for the published field, as well as the original 0 (private), -1 (draft), 1 (publish) values.

To test, you'd want to make sure you can edit the text that appears below the main email content. To do that, navigate to `WooCommerce > Settings > Emails` and edit the `Additional content` field of the emails. The `Thanks` wording appears in the following emails by default:

- Cancelled order
- Processing order
- Completed order
- Customer invoice / Order details
- Customer note
- Reset password

After the change in the text is made, you'd want to place an order and make sure the change appears in emails you receive. 

When testing the new Coupon code generator functionality, focus on the following areas:

- Add, edit, publish, and delete coupon
- Apply coupon during checkout

To test the change around variations, create a variable product without setting the price for variations. Can you see a notice letting you know that some variations are missing the price?

When testing to make sure the refunded amount of the order is visible on the customer facing pages as well, follow the steps below:

- Place an order on the site
- Refund the order partially
- Make sure you can see the amount refunded when navigating to `My Account > Orders > Order refunded` page at the front end of the site

Finally, to test the CSV Import, you'd want to follow the steps below:

- Export products from one of your test sites
- Edit the `Published` field of the CSV file to either `true` = `published` or `false` = `draft`
- Import the file to your current testing site
- Verify that the products that were marked as `true` are indeed published and those marked as `false` are saved as draft 

### Testing helpers

- At any point during your testing, remember to [check your browser's JavaScript console](https://codex.wordpress.org/Using_Your_Browser_to_Diagnose_JavaScript_Errors#Step_3:_Diagnosis) and see if there are any errors reported by WooCommerce.
- Use [Debug Bar](https://wordpress.org/plugins/enable-wp-debug-from-admin-dashboard/) or [Query Monitor](https://en-gb.wordpress.org/plugins/query-monitor/) to help make PHP notices and warnings more noticeable and report anything you see.

### Reporting bugs

- Bugs related to Product Blocks should be reported in the [WooCommerce Gutenberg Products Block repository](https://github.com/woocommerce/woocommerce-gutenberg-products-block)
- Bugs related to WooCommerce REST API should be reported in the [WooCommerce REST API](https://github.com/woocommerce/woocommerce-rest-api) repository
- All other WooCommerce related issues should be reported here in the WooCommerce repository

**Thank you for testing!**


