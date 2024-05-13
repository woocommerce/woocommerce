---
post_title: How to add a section to a settings tab
menu_title: Add a section to a settings tab
tags: how-to
---

When you're adding building an extension for WooCommerce that requires settings of some kind, it's important to ask yourself:  **Where do they belong?**  If your extension just has a couple of simple settings, do you really need to create a new tab specifically for it? Most likely the answer is no.

## When to Create a Section

Let's say we had an extension that adds a slider to a single product page. This extension doesn't have many options, just a couple:

-   Auto-insert into single product page (checkbox)
-   Slider Title (text field)

That's only two options, specifically related to  **Products**. We could quite easily just append them onto the core WooCommerce Products Settings (**WooCommerce > Settings > Products**), but that wouldn't be very user friendly. Users wouldn't know where to look initially so they'd have to scan all of the Products options and it would be difficult / impossible to link the options directly. Fortunately, as of WooCommerce 2.2.2, there is a new filter in place that allows you add a new  **section**, beneath one of the core settings' tabs.

## How to Create a Section

We'll go over doing this through individual functions, but you should probably create a Class that stores all of your settings methods.

The first thing you need to is add the section, which can be done like this by hooking into the  `woocommerce_get_sections_products`  filter:

```php
/**

* Create the section beneath the products tab

**/

add_filter( 'woocommerce_get_sections_products', 'wcslider_add_section' );

function wcslider_add_section( $sections ) {

    $sections['wcslider'] = __( 'WC Slider', 'text-domain' );

    return $sections;

}
```

_[wc-create-section-beneath-products.php](https://gist.github.com/woogists/2964ec01c8bea50fcce62adf2f5c1232/raw/da5348343cf3664c0bc8b6b132d8105bfcf9ca51/wc-create-section-beneath-products.php)_

Make sure you change the  **wcslider**  parts to suit your extension's name / text-domain. The important thing about the  `woocommerce_get_sections_products`  filter, is that the last part  **products**, is the tab you'd like to add a section to. So if you want to add a new tab to accounts section, you would hook into the  `woocommerce_get_sections_accounts`  filter.

## How to Add Settings to a Section

Now that you've got the tab, you need to filter the output of  `woocommerce_get_sections_products`  (or similar). You would add the settings like usual using the  [**WooCommerce Settings API**](https://github.com/woocommerce/woocommerce/blob/trunk/docs/settings-api/), but check for the current section before adding the settings to the tab's settings array. For example, let's add the sample settings we discussed above to the new  **wcslider**  section we just created:

```php
/**

* Add settings to the specific section we created before

*/

add_filter( 'woocommerce_get_settings_products', 'wcslider_all_settings', 10, 2 );

function wcslider_all_settings( $settings, $current_section ) {

/**

* Check the current section is what we want

**/

  if ( $current_section == 'wcslider' ) {
  
      $settings_slider = array();
    
      // Add Title to the Settings
      
      $settings_slider[] = array( 'name' => __( 'WC Slider Settings', 'text-domain' ), 'type' => 'title', 'desc' => __( 'The following options are used to configure WC Slider', 'text-domain' ), 'id' => 'wcslider' );
      
      // Add first checkbox option
      
      $settings_slider[] = array(
      
          'name' => __( 'Auto-insert into single product page', 'text-domain' ),
          
          'desc_tip' => __( 'This will automatically insert your slider into the single product page', 'text-domain' ),
          
          'id' => 'wcslider_auto_insert',
          
          'type' => 'checkbox',
          
          'css' => 'min-width:300px;',
          
          'desc' => __( 'Enable Auto-Insert', 'text-domain' ),
      
      );
      
      // Add second text field option
      
      $settings_slider[] = array(
      
          'name' => __( 'Slider Title', 'text-domain' ),
          
          'desc_tip' => __( 'This will add a title to your slider', 'text-domain' ),
          
          'id' => 'wcslider_title',
          
          'type' => 'text',
          
          'desc' => __( 'Any title you want can be added to your slider with this option!', 'text-domain' ),
      
      );
      
      $settings_slider[] = array( 'type' => 'sectionend', 'id' => 'wcslider' );
      
      return $settings_slider;
    
    /**
    
    * If not, return the standard settings
    
    **/
    
    } else {
    
        return $settings;
  
    }

}

```

_[wc-add-settings-section.php](https://gist.github.com/woogists/4038b83900508806c57a193a2534b845#file-wc-add-settings-section-php)_

We're hooking into the same  `woocommerce_get_sections_products`  filter, but this time doing a check that the  `$current_section`  matches our earlier defined custom section (wcslider), before adding in our new settings.

## Using the New Settings

You would now just use your newly created settings like you would any other WordPress / WooCommerce setting, through the  [**get_option**](http://codex.wordpress.org/Function_Reference/get_option)  function and the defined ID of the setting. For example, to use the previously created  **wcslider_auto_insert**  option, simply use the following code:  `get_option( 'wcslider_auto_insert' )`

## Conclusion

When creating an extension for WooCommerce, think about where your settings belong before you create them. The key to building a useful product is making it easy to use for the end user, so appropriate setting placement is crucially important. For more specific information on adding settings to WooCommerce, check out the  [**Settings API documentation**](https://github.com/woocommerce/woocommerce/blob/trunk/docs/extension-development/settings-api.md).
