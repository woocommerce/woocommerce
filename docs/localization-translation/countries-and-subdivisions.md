---
post_title: Updating Countries and Subdivisions
menu_title: Countries and Subdivisions
tags: how-to
---

WooCommerce comes complete with a comprehensive list of countries and subdivisions (such as provinces or states) that are used in various parts of the user interface.

Of course, even countries and their subdivisions periodically change. In these cases, you can certainly file a [bug report](https://github.com/woocommerce/woocommerce/issues/new?template=1-bug-report.yml) or [submit a pull request](/docs/category/contributing/). However, it is important to understand that our policy is only to accept changes if they align with the current version of the [CLDR project](https://cldr.unicode.org/). Therefore, it is generally best to review that and, if necessary, propose a change there first before asking that it be adopted by WooCommerce.

This approach may not be suitable in all cases, because it can take time for CLDR to accept updates. In such cases, you can still modify the lists of countries and subdivisions by using custom snippets like the following:

- [Snippet to add a country](../code-snippets/add-a-country.md)
- [Snippet to add or modify states](../code-snippets/add-or-modify-states.md)

