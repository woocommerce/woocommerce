# Compatibility Layer - [AbstractTemplateCompatibility.php](https://github.com/woocommerce/woocommerce-blocks/blob/trunk/src/Templates/AbstractTemplateCompatibility.php)

The Compatibility Layer ensures that blockified templates work correctly with extensions that use hooks to extend their behavior. It appends/pre-appends the corresponding hooks to each block. Also, it removes the default callbacks added to those hooks by WooCommerce.

The Compatibility Layer is disabled when either of classic template blocks are added on the page:

- `WooCommerce Single Product`,
- `WooCommerce Product Attribute`,
- `WooCommerce Product Taxonomy`,
- `WooCommerce Product Tag`,
- `WooCommerce Product Search Results`,
- `WooCommerce Product Grid`.

Please note these blocks represent classic templates. As an example, using Single Product block won't disable Compatibility Layer.

Furthermore, it is possible to disable the compatibility layer via the hook: [`woocommerce_disable_compatibility_layer`](https://github.com/woocommerce/woocommerce-blocks/blob/trunk/src/Templates/AbstractTemplateCompatibility.php/#L41-L42).

## Archive Product Templates - [ArchiveProductTemplatesCompatibility](https://github.com/woocommerce/woocommerce-blocks/blob/trunk/src/Templates/ArchiveProductTemplatesCompatibility.php)

The compatibility is built around the Products block because the loop is the main element of archive templates and hooks are placed inside and around the loop. The Compatibility Layer injects custom attributes for the Products block that inherits query from the template and its inner blocks.

The following table shows where the hooks are injected into the page.


| Hook Name                               | Block Name       | Position |
|-----------------------------------------|------------------|----------|
| woocommerce_before_main_content         | Products         | before   |
| woocommerce_after_main_content          | Products         | after    |
| woocommerce_before_shop_loop_item_title | Product Title    | before   |
| woocommerce_shop_loop_item_title        | Product Title    | after    |
| woocommerce_after_shop_loop_item_title  | Product Title    | before   |
| woocommerce_before_shop_loop_item       | Loop Item        | before   |
| woocommerce_after_shop_loop_item        | Loop Item        | after    |
| woocommerce_before_shop_loop            | Product Template | before   |
| woocommerce_after_shop_loop             | Product Template | after    |
| woocommerce_no_products_found           | No Results       | before   |
| woocommerce_archive_description         | Term Description | before   |






## Single Product Templates - [SingleProductTemplateCompatibility](https://github.com/woocommerce/woocommerce-blocks/blob/c8d82b20f4e4b8a424f1f0ebff80aca6f62588e5/src/Templates/SingleProductTemplateCompatibility.php)

The compatibility is built around the entire page. The classic Single Product Page has a main div with the class `product` that wraps all the elements, which has multiple classes: the style of the elements inside the wrapper is applied via CSS with the selector `.product`. For this reason, the Compatibility Layer wraps inside a div with the class `product` all the blocks related to the Single Product Template that aren’t interrupted by a template part.

The following table shows where the hooks are injected into the page.


| Hook                                      | Block Name                                                                                                                                             | Position |
|-------------------------------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------|----------|
| woocommerce_before_main_content           | First block related to the Single Product Template (Product Image Gallery, Product Details, Add to Cart Form, Product Meta, Product Price, Breadcrumbs) | before   |
| woocommerce_after_main_content            | Last block related to the Single Product Template (Product Image Gallery, Product Details, Add to Cart Form, Product Meta, Product Price, Breadcrumbs)  | after    |
| woocommerce_sidebar                       | Last block related to the Single Product Template (Product Image Gallery, Product Details, Add to Cart Form, Product Meta, Product Price, Breadcrumbs)  | after    |
| woocommerce_before_single_product         | First block related to the Single Product Template (Product Image Gallery, Product Details, Add to Cart Form, Product Meta, Product Price, Breadcrumbs) | before   |
| woocommerce_before_single_product_summary | First block related to the Single Product Template (Product Image Gallery, Product Details, Add to Cart Form, Product Meta, Product Price, Breadcrumbs) | before   |
| woocommerce_single_product_summary        | First `core/post-excerpt` block                                                                                                                         | before   |
| woocommerce_after_single_product          | Last block related to the Single Product Template (Product Image Gallery, Product Details, Add to Cart Form, Product Meta, Product Price, Breadcrumbs)  | after    |
| woocommerce_product_meta_start            | Product Meta                                                                                                                                           | before   |
| woocommerce_product_meta_end              | Product Meta                                                                                                                                           | after    |
| woocommerce_share                         | Product Details                                                                                                                                        | before   |
| woocommerce_after_single_product_summary  | Product Details                                                                                                                                        | before   |
