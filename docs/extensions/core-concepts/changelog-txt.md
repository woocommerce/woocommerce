---
post_title: Formatting for Changelog.txt
sidebar_label: Changelog.txt
---

# Formatting for Changelog.txt

## The changelog.txt file

WooCommerce extensions use a standard changelog format. A changelog.txt file is required for extensions on the WooCommerce Marketplace.

Your `changelog.txt` file should look like this:

```php
*** WooCommerce Extension Name Changelog ***

YYYY-MM-DD - version 1.1.0
* Added - Useful new feature
    * A second new feature
    * A third new feature
* Fixed - Important bug fix
* Update - Compatibility with latest version

YYYY-MM-DD - version 1.0.1
* Fixed a bug

YYYY-MM-DD - version 1.0.0
* Initial release
```

## Changelog entry types

To showcase the different types of work done in a product update, use any of the following words to denote what type of change each line is:

- add
- added
- feature
- new
- developer
- dev
- tweak
- changed
- update
- delete
- remove
- fixed
- fix

## Changelog nesting

Items indented under a specific entry type can nest additional changes of the same type in a single-level list. 

## Example changelog.txt

This is how a parsed changelog appears in a modal on WooCommerce.com, with each entry type replaced by an icon. Changelog entries without a type are displayed with the Other icon, while nested entries have no icon. 

![Example changelog as shown on WooCommerce.com](https://woocommerce.com/wp-content/uploads/2025/11/changelog-formatting.png)
