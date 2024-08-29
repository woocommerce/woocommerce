# Local Pickup testing

## Managing Local Pickup

### Enabling local pickup

1. In a fresh website that uses Checkout shortcode as its main Checkout, go to **WooCommerce -> Settings -> Shipping**, you should **not** see a local pickup tab there.
2. Switch to using Checkout block for your main Checkout by removing the shortcode and adding Checkout block.
3. In your Checkout block, you should not see the shipping method toggle or pickup options block.
3. Visit **WooCommerce -> Settings -> Shipping** again, you should see a Local Pickup tab there.
4. You should be able to toggle local pickup on from that screen.
5. In your Checkout page in the editor, you should see your shipping method and pickup options block.

### Adding locations

1. In **WooCommerce -> Settings -> Shipping -> Local Pickup**, you should see Pickup locations section below.
2. If you don't have any pickup locations, you should see the message "When you add a pickup location, it will appear here".
3. Clicking "Add pickup location" should open up a modal.
4. You should be able to fill up the title, address, and details.
5. States would change depending on what country you select.
6. Countries shouldn't be limited to the ones your store sell to, it should provide all possible countries.
7. Hitting save should add a new location.
8. You can repeat the above flow several times for several pickup locations.
9. Persist your changes by hitting "save changes".
10. Refresh the page after you saved, locations you added should be there.

### Editing locations

1. On a location, you can edit it by clicking "edit".
2. The modal will load with the location details.
3. Changing those values and hitting save should be reflected in the pickup location table.
4. Saving and reloading the page should persist your changes.

### Removing locations

1. On a location, you can edit it by clicking "edit".
2. This should load up the modal with an additional button "delete location".
3. Hitting "Delete location" should close the modal and remove the location from the table.
4. Saving and reloading the page should persist your changes.

### Adding local pickup price

1. On the general section of Local Pickup, you can enable adding a price by checking "Add a price for customers who choose local pickup".
2. Checking that would open up 2 new fields, cost and taxes.
3. You should be able to add a numeric value to cost.
4. You can select between Taxable and Not Taxable for the taxes select.
5. Saving and reloading the page should persist your changes.

### Editing Shipping methods toggle titles

1. Visit your Checkout block page on the editor.
2. You should be able to click on the toggle buttons to edit them.
3. Change the text of one button and save the page.
4. Visit Checkout on the frontend, you should see your newly changed text.
5. In the editor, remove the whole text, you should see a placeholder of "Local Pickup" or "Shipping".
6. Save without text.
7. In the frontend, you should see the default texts instead of an empty string.

### Enabling and disabling icons and pricing

1. Visit your Checkout block page on the editor.
2. Select the shipping method block.
3. You should see a couple of options in the sidebar "show icons" "show costs".
4. Toggling them off should remove them from the block.
5. Save with them toggled off, visit the frontend, you should not see the icons or the costs depending on your options.
6. Toggling them back on should work.

### Toggling views in the editor

1. Visit your Checkout block page on the editor.
2. Select the shipping method block.
3. Clicking on "Local Pickup" toggle should turn the pickup options block on.
4. Clicking on "Shipping" toggle should hide the pickup options block and show the shipping options block.

## General testing

### Local Pickup enabled with no locations

1. In **WooCommerce -> Settings -> Shipping -> Local Pickup**, enable local pickup.
2. Add a pickup location, but disable it and save.
3. Visit the frontend of your website, add an item to cart, and navigate to Checkout.
4. You should **not** see the shipping method toggle.
5. You should only see the shipping options block.
6. Go to **WooCommerce -> Settings -> Shipping -> Local Pickup** again, now remove the location.
7. Visit the Checkout, you should **not** see the shipping method toggle.

### Local Pickup enabled with locations

1. In **WooCommerce -> Settings -> Shipping -> Local Pickup**, enable local pickup.
2. Add pickup locations and enable them.
3. Visit the frontend of your website, add an item to cart, and navigate to Checkout.
4. You should see the toggle.
5. Select "Shipping" in the toggle.
6. It should **not** have any pickup locations in it.
7. Select "Local Pickup", it should only have pickup locations in it.
8. Changing the view should change the selected shipping method and option to the first one in the view, i.e, changing to local pickup should select the first pickup location there.
9. Placing an order using local pickup should persist your option.

### Shipping selector toggle with different min/max prices for shipping

1. **WooCommerce -> Settings -> Shipping -> Shipping zones**, create a new zone.
2. In it, add 3 flat rates options, priced all differently (for example, $10, $20, $50).
3. Only have those 3 enabled.
4. Visit the frontend of your website, add an item to cart, and navigate to Checkout.
5. In the "Shipping" toggle, you should see the price tag as "from $10.00".
6. Disable the $50 rate, change the $20 rate cost to $10.
7. In Checkout, the price tag should say "$10.00" only, without the "from".
8. Change one of the $10 rates to be $0.
9. In Checkout, the price tag should say "from FREE".
10. **WooCommerce -> Settings -> Shipping -> Local Pickup**, leave the cost checkbox unchecked.
11. In Checkout, the local pickup toggle should say "FREE".
12. Change the local pickup cost to $10.
13. In the frontend, it should say "$10".

### Local pickup in cart

[Enhancement pending](https://github.com/woocommerce/woocommerce-blocks/issues/7997)

1. In Cart, you should see both pickup locations and shipping rates together for a package.
2. Selecting a pickup location should change the text of "shipping to \[customer address\]" to "Pick up from \[location address\]".
3. Selecting Local Pickup or regular shipping in Cart should be carried over to Checkout.

### Taxes

1. Setup two different tax rates, one for your store base address for 10%, one for a different address for 20%.
2. Create a pickup location with an address that matches the 20% tax address.
3. Create a pickup location without an address.
4. On Checkout, select regular shipping, use an address that doesn't match the ones above, you should **not** see a tax line item.
6. Switch to local pickup, and select the location without an address, you should see the 10% rate being applied.
5. Select the location with 20% tax address, you should see a 20% tax being applied.
6. Place an order with that pickup location selected, the final tax amount in the thank you page should reflect that.


## Multiple Packages

To test multiple packages, you need to install and configure a plugin:

**Multiple Package for WooCommerce**:

1. Install [**Multiple Packages for WooCommerce**](https://wordpress.org/plugins/multiple-packages-for-woocommerce/).
2. In **WooCommerce -> Settings -> Multiple Packages**, enable Multiple packages.
3. Choose group by Shipping class.
4. Leave the shipping settings intact for now.
5. In **WooCommerce -> Settings -> Shipping -> Shipping classes** introduce a new shipping class "Not pickable".
6. Go to one of your products edit page, scroll down to **Product Data -> Shipping** and change the shipping class to the newly created one "Not pickable".

### Multiple packages that all support Local Pickup

1. Add a regular product to your cart and a "Not pickable" product.
2. Visit Checkout block.
3. Select the "Shipping" toggle.
4. Scroll down to shipping options, you should see two packages and should be able to select any rates from them.
5. In the toggle, select "Local Pickup"
6. In Pickup options, you should see a single package.
7. The rate price should be [price x number of packages], in this case it would be x 2.
8. Your shipping subtotal should reflect that.

### Multiple packages in Cart

1. Add a regular product to your cart and a "Not pickable" product.
2. Visit Cart block.
3. You should see 2 packages in the shipping section.
4. Both packages should be collapsed by default with a title and items below it.
5. Opening packages, you should be able to see both regular rates and pickup locations.
6. In both packages, select a regular rate.
7. Switch one package rate to a pickup location.
8. A warning notice will show up saying "Multiple shipments must have the same pickup location".
9. The other package selected rate will sync up to the rate you selected.
10. You can't select different pickup locations or mixed options between packages.

### Multiple packages in which one doesn't support Local Pickup

1. Go to **WooCommerce -> Settings -> Multiple Packages**, in the **Shipping Method Restrictions** section, uncheck local pickup for the Not pickable class.
2. Add a regular product and a Not pickable product to your cart, go to Checkout.
3. Shipping method toggle should no longer be visible, and you can only select regular shipping methods.
4. In Cart block, you should not be able to see pickup locations in packages.
5. Removing the restricted product should show local pickup again.

## Conflict and disabling

### Enabling Shipping zone local pickup and new local pickup at same time

1. In **WooCommerce -> Settings -> Shipping -> Shipping zones** add a class local pickup method.
2. Go to  **WooCommerce -> Settings -> Shipping -> Local Pickup**, you should see a warning saying "Enabling this will produce duplicate options at checkout. Remove the local pickup shipping method from your shipping zones".
3. Back to the shipping zone, disable the local pickup rate (without removing it) and head back to Local Pickup.
4. The notice is no longer visible.

### Error handling

#### Pickup title

1. In  **WooCommerce -> Settings -> Shipping -> Local Pickup**, empty the title field and try to save.
2. The save button should be grayed out.
3. The title input should be with an error message "title is required".

#### Location title

1. Try to add a new location without adding the location title.
2. The "Done" button should be grayed out.
3. The location name title should be with an error message "location name is required".

### Switching back to shortcode

1. Local Pickup should be enabled and has pickup locations inside of it.
2. Go to your Checkout edit page, remove the Checkout block, and insert a shortcode block with `[woocommerce_checkout]`, save the page.
3. In the frontend, you should not see pickup locations.

### Disabling Local Pickup

1. Make Checkout block your default Checkout.
2. Go to **WooCommerce -> Settings -> Shipping -> Local Pickup** and disable local pickup.
3. Visit the frontend, you should not see any locations or the toggle.
