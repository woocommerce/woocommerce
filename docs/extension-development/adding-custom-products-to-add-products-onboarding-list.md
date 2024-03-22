---
post_title: How to add custom product types to Add Products onboarding list
menu_title: Add custom product types to Add Products onboarding list
tags: how-to
---

## Introduction

WooCommerce allows developers to extend the product type onboarding list, offering a more customizable and engaging experience during the Add Products onboarding task. This tutorial will guide you through adding custom product types to your WooCommerce store using the `experimental_woocommerce_tasklist_product_types` JavaScript filter.

## Prerequisites

- A basic understanding of JavaScript and PHP.
- WooCommerce 8.8 or later installed on your WordPress site.

## Step 1: Adding a JavaScript Filter

To add a new product type to the onboarding list, we'll utilize the `@wordpress/hooks` package, specifically the addFilter function. If you're not already familiar, `@wordpress/hooks` allows you to modify or extend features within the WordPress and WooCommerce ecosystem without altering the core code.

First, ensure you have the `@wordpress/hooks` package installed. If not, you can add it to your project using `npm` or `yarn`:

`npm install @wordpress/hooks`

or:

`yarn add @wordpress/hooks`

Next, add the following JavaScript code to your project. This code snippet demonstrates how to add a "custom product" type to the onboarding list:

```javascript
/**
* External dependencies
*/
import { addFilter } from '@wordpress/hooks';
import { Icon, chevronRight } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import FolderMultipleIcon from 'gridicons/dist/folder-multiple';

addFilter(
   'experimental_woocommerce_tasklist_product_types',
   'custom-product',
   (productTypes) => [
       ...productTypes,
       {
           key: 'custom-product',
           title: __('Custom product', 'custom-product'),
           content: __('Create an awesome custom product.', 'custom-product'),
           before: <FolderMultipleIcon />,
           after: <Icon icon={chevronRight} />,
           onClick: () => {
           }
       },
   ]
);
```

This filter adds a new product type called "Custom Product" with a brief description and icons before and after the title for a visually appealing presentation.

## Step 2: Optional - Customizing the onClick Handler

By default, if no onClick handler is supplied, the onboarding task will utilize the default CSV template handler. To customize this behavior, you can specify your own onClick handler within the product type object.

## Step 3: Modifying the CSV Template Path (Optional)

If you wish to use a different CSV template for your custom product type, you can modify the template path using the woocommerce_product_template_csv_file_path filter in PHP. Here's an example of how to change the template path:

```php
add_filter('woocommerce_product_template_csv_file_path', function($path) {
   // Specify your custom template path here
   return $newPath;
});
```
## Conclusion

With WooCommerce, extending the product type onboarding list is straightforward and offers significant flexibility for customizing the onboarding experience. By following the steps outlined in this tutorial, you can enhance your WooCommerce store and make the Add Products task more relevant and helpful to your specific needs.
