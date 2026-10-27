# Lookups that can return nothing

**Source:**

- #62102 / #61628 (10.3.6): `needs_shipping()` with a deleted product.
- #62074: orders containing deleted products.
- #68369: variation whose parent was deleted.
- #63982: deleted attribute in filters.
- #64252: a filter returned a non-product.
- #69231: null where a post was expected.
- #58487 (9.9.2): a `WC_Email` child without `template_html` fatalled.
- #64410, #53762 (9.5.1).
- #68573: `get_country_locale()` could now return an empty array. The PR guarded `OrderController` but
  not `WC_Customer::has_full_shipping_address()`, which still read `['default']`. Fixed in #69108.

**What broke:** Code dereferenced a lookup that returned false or null, or an object missing expected properties. Most often a deleted product, parent, or attribute still referenced by an order or a filter.

**Pattern to catch:** `wc_get_product()` / `wc_get_order()` / `get_post()` / `get_term()` / `->get_product()` results, or objects from a filter, dereferenced without a falsy or `instanceof` check. Also iterating `WC_Email` subclasses (or any registry of extension classes) and reading properties they may not set, and unguarded array keys.

Also the producer side: a function that can now return empty, `null` or `false`. Every caller that
reads into the result needs the same guard, including callers in files the diff does not touch.

**How to confirm:** Find the lookup and check for `if ( ! $x )` or `instanceof`. Ask whether the ID can point at a trashed or deleted entity. Orders keep their line items after the product is deleted.

**Don't flag when:** The object comes from a constructor in the same scope, or the path is behind an earlier guard you can quote.
