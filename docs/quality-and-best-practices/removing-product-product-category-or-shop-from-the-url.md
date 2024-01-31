---
post_title: Understanding the risks of removing URL bases in WooCommerce
menu_title: Risks of removing URL bases
tags: reference
---

## In sum

Removing  `/product/`,  `/product-category/`, or  `/shop/`  from the URLs is not advisable due to the way WordPress resolves its URLs. It uses the  `product-category`  (or any other text for that matter) base of a URL to detect that it is a URL leading to a product category. There are SEO plugins that allow you to remove this base, but that can lead to a number of problems with performance and duplicate URLs.

## Better to avoid

You will make it harder for WordPress to detect what page you are trying to reach when you type in a product category URL. Also, understand that the standard "Page" in WordPress always has no base text in the URL. For example:

- `http://yoursite.com/about-page/` (this is the URL of a standard page)
- `http://yoursite.com/product-category/category-x/` (this is the URL leading to a product category)

What would happen if we remove that 'product-category' part?

-   `http://yoursite.com/about-page/`
-   `http://yoursite.com/category-x/`

WordPress will have to do much more work to detect what page you are looking for when entering one of the above URLs. That is why we do not recommend using any SEO plugin to achieve this.
