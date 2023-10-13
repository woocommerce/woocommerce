# Shortcodes included with WooCommerce

https://woocommerce.wistia.com/medias/y60wnvsmiy?embedType=async&seo=false&videoFoam=true&videoWidth=800

WooCommerce comes with several shortcodes that can be used to insert content inside posts and pages.

Our WooCommerce blocks are now the easiest and most flexible way to display your products on posts and pages on your WooCommerce site. Read more about all the available [WooCommerce blocks here](https://woocommerce.com/document/woocommerce-blocks/).

**Help us shape the Future at WooCommerce!** Our team is always working to build tools that will help your business to succeed and feedback plays a vital role in that process. To make sure that our products are the best they can be, we regularly conduct **research studies with our customers – and we’d love to add your voice to the conversation.** Learn more [here](https://woocommerce.com/customer-research/).

## How to use shortcodes

### Where to use

Shortcodes can be used on pages and posts in WordPress. If you are using the block editor, there is a shortcode block you can use to **paste the shortcode** in.

![](https://woocommerce.com/wp-content/uploads/2020/03/shortcodes-2.png?w=950)

If you are using the classic editor, you can paste the shortcode on the page or post.

### Args (or Arguments)

Several of the shortcodes below will mention “Args”. These are ways to make the shortcode more specific. For example, by adding `id="99"` to the `[add_to_cart]` shortcode, it will create an add-to-cart button for the product with ID 99.

## Page Shortcodes

WooCommerce cannot function properly without the first three shortcodes being somewhere on your site.

Note: You can now test the new cart and checkout blocks that are available in the [WooCommerce Blocks plugin](https://woocommerce.wordpress.com/2020/05/27/available-for-testing-a-block-based-woocommerce-cart-and-checkout/)!

**`woocommerce_cart`** – shows the cart page  
**`woocommerce_checkout`** – shows the checkout page  
**`woocommerce_my_account`** – shows the user account page  
**`woocommerce_order_tracking`** – shows the order tracking form

In most cases, these shortcodes will be added to pages automatically via our [onboarding wizard](https://woocommerce.com/document/woocommerce-onboarding-wizard/) and do not need to be used manually.

### Cart

Used on the cart page, the cart shortcode displays cart content and interface for coupon codes and other cart bits and pieces.

Args: none

\[woocommerce_cart\]

### Checkout

Used on the checkout page, the checkout shortcode displays the checkout process.

Args: none

\[woocommerce_checkout\]

### My Account

Shows the ‘my account’ section where the customer can view past orders and update their information. You can specify the number of orders to show. By default, it’s set to 15 (use **\-1** to display **all orders**.)

Args:

array(
     'current_user' => ''
)

woocommerce_my_account

Current user argument is automatically set using `get_user_by( 'id', get_current_user_id() )`.

### Order Tracking Form

Lets a user see the status of an order by entering their order details.

Args: none

\[woocommerce_order_tracking\]

## Products

**Note:** Since version 3.6, WooCommerce Core includes several product blocks. These are easier to configure than shortcodes, so if you are using the WordPress block editor, you may want to read more [about WooCommerce Blocks](https://woocommerce.com/document/woocommerce-blocks/) first.

The `[products]` shortcode is one of our most robust shortcodes, which can replace various other strings used in earlier versions of WooCommerce.

The `[products]` shortcode allows you to display products by post ID, SKU, categories, attributes, with support for pagination, random sorting, and product tags, replacing the need for multiples shortcodes such as  `[featured_products]`, `[sale_products]`, `[best_selling_products]`, `[recent_products]`, `[product_attribute]`, and `[top_rated_products]`, which are needed in versions of WooCommerce below 3.2. Review the examples below.

### Available Product Attributes

The following attributes are available to use in conjunction with the `[products]` shortcode. They have been split into sections for primary function for ease of navigation, with examples below.

#### Display Product Attributes

-   `limit` – The number of products to display. Defaults to and `-1` (display all)  when listing products, and `-1` (display all) for categories.
-   `columns` – The number of columns to display. Defaults to `4`.
-   `paginate` – Toggles pagination on. Use in conjunction with `limit`. Defaults to `false` set to `true` to paginate .
-   `orderby` – Sorts the products displayed by the entered option. One or more options can be passed by adding both slugs with a space between them. Available options are:
    -   `date` – The date the product was published.
    -   `id` – The post ID of the product.
    -   `menu_order` – The Menu Order, if set (lower numbers display first).
    -   `popularity` – The number of purchases.
    -   `rand` – Randomly order the products on page load (may not work with sites that use caching, as it could save a specific order).
    -   `rating` – The average product rating.
    -   `title` – The product title. This is the default `orderby` mode.
-   `skus` – Comma-separated list of product SKUs.
-   `category` – Comma-separated list of category slugs.
-   `tag` – Comma-separated list of tag slugs.
-   `order` – States whether the product order is ascending (`ASC`) or descending (`DESC`), using the method set in `orderby`. Defaults to `ASC`.
-   `class` – Adds an HTML wrapper class so you can modify the specific output with custom CSS.
-   `on_sale` – Retrieve on sale products. Not to be used in conjunction with `best_selling`or `top_rated`.
-   `best_selling` – Retrieve the best selling products. Not to be used in conjunction with `on_sale` or `top_rated`.
-   `top_rated` – Retrieve top-rated products. Not to be used in conjunction with `on_sale`or `best_selling`.

#### Content Product Attributes

-   `attribute` – Retrieves products using the specified attribute slug.
-   `terms` – Comma-separated list of attribute terms to be used with `attribute`.
-   `terms_operator` – Operator to compare attribute terms. Available options are:
    -   `AND` – Will display products from all of the chosen attributes.
    -   `IN` – Will display products with the chosen attribute. This is the default `terms_operator` value.
    -   `NOT IN` – Will display products that are not in the chosen attributes.
-   `tag_operator` – Operator to compare tags. Available options are:
    -   `AND` – Will display products from all of the chosen tags.
    -   `IN` – Will display products with the chosen tags. This is the default `tag_operator` value.
    -   `NOT IN` – Will display products that are not in the chosen tags.
-   `visibility` – Will display products based on the selected visibility. Available options are:

    -   `visible` – Products visible on shop and search results. This is the default `visibility` option.
    -   `catalog` – Products visible on the shop only, but not search results.
    -   `search` – Products visible in search results only, but not on the shop.
    -   `hidden` – Products that are hidden from both shop and search, accessible only by direct URL.
    -   `featured` – Products that are marked as Featured Products.

-   `category` – Retrieves products using the specified category slug.
-   `tag` – Retrieves products using the specified tag slug.
-   `cat_operator` – Operator to compare category terms. Available options are:

    -   `AND` – Will display products that belong in all of the chosen categories.
    -   `IN` – Will display products within the chosen category. This is the default `cat_operator` value.
    -   `NOT IN` – Will display products that are not in the chosen category.

-   `ids` – Will display products based on a comma-separated list of Post IDs.
-   `skus` – Will display products based on a comma-separated list of SKUs.

If the product is not showing, make sure it is not set to “Hidden” in the “Catalog Visibility”.

To find the Product ID, go to the **Products** screen, hover over the product and the ID appears as shown below.

[![Finding the WooCommerce product ID by hovering over a product](https://woocommerce.com/wp-content/uploads/2012/01/woo_find_product_id.png)](https://woocommerce.com/wp-content/uploads/2012/01/woo_find_product_id.png)

#### Special Product Attributes

These attributes cannot be used with the “Content Attributes” listed above, as they will likely cause a conflict and not display. You should only use one of the following special attributes.

-   `best_selling` – Will display your best selling products. Must be set to `true`.
-   `on_sale` – Will display your on-sale products. Must be set to `true`.

### Examples of Product Scenarios

In the following scenarios, we’ll use an example clothing store.

#### Scenario 1 – Random Sale Items

I want to display four random on sale products.

\[products limit="4" columns="4" orderby="popularity" class="quick-sale" on_sale="true" \]

This shortcode explicity states four products with four columns (which will be one row), showing the most popular on-sale items. It also adds a CSS class `quick-sale`, which I can modify in my theme.

![WooCommerce Shortcode - Sale Products](https://woocommerce.com/wp-content/uploads/2017/10/shortcode-sale.png)

#### Scenario 2 – Featured Products

I want to display my featured products, two per row, with a maximum of four items.

\[products limit="4" columns="2" visibility="featured" \]

This shortcode says up to four products will load in two columns, and that they must be featured. Although not explicitly stated, it uses the defaults such as sorting by title (A to Z).

![WooCommerce Shortcode - Featured Products](https://woocommerce.com/wp-content/uploads/2017/10/shortcode-featured.png)

#### Scenario 3 – Best Selling Products

I want to display my three top best selling products in one row.

\[products limit="3" columns="3" best_selling="true" \]

![WooCommerce Shortcode - Best Selling Products](https://woocommerce.com/wp-content/uploads/2017/10/shortcode-bestselling.png)

#### Scenario 4 – Newest Products

I want to display the newest products first – four products across one row. To accomplish this, we’ll use the Post ID (which is generated when the product page is created), along with the order and orderby command. Since you can’t see the Post ID from the frontend, the ID#s have been superimposed over the images.

\[products limit="4" columns="4" orderby="id" order="DESC" visibility="visible"\]

![WooCommerce Shortcodes - Newest](https://woocommerce.com/wp-content/uploads/2012/01/shortcodes-newest.png)

#### Scenario 5 – Specific Categories

I only want to display hoodies and shirts, but not accessories. I’ll use two rows of four.

\[products limit="8" columns="4" category="hoodies, tshirts" cat_operator="AND"\]

![WooCommerce Shortcode - Products by Category](https://woocommerce.com/wp-content/uploads/2017/10/shortcode-categories1.png)

Alternatively, I only want to display products not in those categories. All I need to change is the `cat_operator` to `NOT IN`.

\[products limit="8" columns="4" category="hoodies, tshirts" cat_operator="NOT IN"\]

Note that even though the limit is set to `8`, there are only four products that fit that criteria, so four products are displayed.

![WooCommerce Shortcode - Products by Category](https://woocommerce.com/wp-content/uploads/2017/10/shortcode-categories2.png)

#### Scenario 6 – Attribute Display

Each of the clothing items has an attribute, either “Spring/Summer” or “Fall/Winter” depending on the appropriate season, with some accessories having both since they can be worn all year. In this example, I want three products per row, displaying all of the “Spring/Summer” items. That attribute slug is `season`, and the attributes are `warm` and `cold`. I also want them sorted from the newest products to the oldest.

\[products columns="3" attribute="season" terms="warm" orderby="date"\]

![WooCommerce Shortcode - Products by Attribute](https://woocommerce.com/wp-content/uploads/2017/10/shortcode-attribute1.png)

Alternatively, if I wanted to display exclusively cold weather products, I could add `NOT IN` as my `terms_operator`:

\[products columns="3" attribute="season" terms="warm" terms_operator="NOT IN"\]

![WooCommerce Shortcode - Products by Attribute](https://woocommerce.com/wp-content/uploads/2017/10/shortcode-attribute2.png)

Note that by using `NOT IN`, I exclude products that are both in “Spring/Summer” and “Fall/Winter”. If I wanted to show all cold-weather appropriate gear including these shared accessories, I would change the term from `warm` to `cold`.

#### Scenario 7 – Show Only Products With tag “hoodie”

\[products tag="hoodie"\]

![](https://woocommerce.com/wp-content/uploads/2012/01/screen-shot-2018-05-09-at-12-35-12.png?w=550)

### [Sorting Products by Custom Meta Fields

**Note:** We are unable to provide support for customizations under our [Support Policy](https://woocommerce.com/support-policy/). If you are unfamiliar with code/templates and resolving potential conflicts, you can contact a [WooExpert](https://woocommerce.com/wooexperts/).

When using the Products shortcode, you can choose to order products by the pre-defined values above. You can also sort products by custom meta fields using the code below (in this example we order products by price):

add_filter( 'woocommerce_shortcode_products_query', 'woocommerce_shortcode_products_orderby' );

function woocommerce_shortcode_products_orderby( $args ) {

    $standard\_array = array('menu\_order','title','date','rand','id');

    if( isset( $args\['orderby'\] ) && !in\_array( $args\['orderby'\], $standard\_array ) ) {
        $args\['meta\_key'\] = $args\['orderby'\];
        $args\['orderby'\]  = 'meta\_value\_num';
    }

    return $args;

}

You need to place this snippet in functions.php in your theme folder and then customize it by editing the meta_key.

## Product Category

These two shortcodes will display your product categories on any page.

-   `[product_category]` – Will display products in a specified product category.
-   `[product_categories]` – Will display all your product categories.

### Available Product Category attributes

-   `ids` – Specify specific category ids to be listed. To be used in \[product_categories\]
-   `category` – Can be either the category id, name or slug. To be used in \[product_category\]
-   `limit` – The number of categories to display
-   `columns` – The number of columns to display. Defaults to 4
-   `hide_empty` – The default is “1” which will hide empty categories. Set to “0” to show empty categories
-   `parent` – Set to a specific category ID if you would like to display all the child categories. Alternatively, set to “0” (like in the example below) to show only the top level categories.
-   `orderby` – The default is to order by “name”, can be set to “id”, “slug”, or “menu_order”. If you want to order by the ids you specified then you can use `orderby="include"`
-   `order` – States whether the category ordering is ascending (`ASC`) or descending (`DESC`), using the method set in `orderby`. Defaults to `ASC`.

### Examples of Product Category Scenarios

#### Scenario 8 – Show Top Level Categories Only

Imagine you only wanted to show top level categories on a page and exclude the sub categories, well it’s possible with the following shortcode.

\[product_categories number="0" parent="0"\]

[![](https://woocommerce.com/wp-content/uploads/2012/01/woocommerce-shortcodes-top-level-categories-only.png?w=950)](https://woocommerce.com/wp-content/uploads/2012/01/woocommerce-shortcodes-top-level-categories-only.png)

## Product Page

Show a full single product page by ID or SKU.

\[product_page id="99"\]

\[product_page sku="FOO"\]

## Related Products

List related products.

Args:

array(
'limit' => '12',
'columns' => '4',
'orderby' => 'title'
)

`[related_products limit="12"]`

### limit Argument

**Note:** the ‘limit’ shortcode argument will determine how many products are shown on a page. This will not add pagination to the shortcode.

## Add to Cart

Show the price and add to cart button of a single product by ID.

Args:

array(
'id' => '99',
'style' => 'border:4px solid #ccc; padding: 12px;',
'sku' => 'FOO'
'show_price' => 'TRUE'
'class' => 'CSS-CLASS'
'quantity' => '1';
)

    [add_to_cart id="99"]

## Add to Cart URL

Display the URL on the add to cart button of a single product by ID.

Args:

array(
'id' => '99',
'sku' => 'FOO'
)

\[add_to_cart_url id="99"\]

## Display WooCommerce notifications on pages that are not WooCommerce

`[shop_messages]` allows you to show WooCommerce notifications (like, ‘The product has been added to cart’) on non-WooCommerce pages. Helpful when you use other shortcodes, like `[add_to_cart]`, and would like the users to get some feedback on their actions.

## Troubleshooting Shortcodes

If you correctly pasted your shortcodes and the display looks incorrect, make sure you did not embed the shortcode between **<pre>** tags. This is a common issue. To **remove** these tags, edit the page, and click the Text tab:

![Remove Pre Tags from Shortcode](https://woocommerce.com/wp-content/uploads/2012/01/WooCommerce-Shortcode-Pre-Tags.png)

Another common problem is that straight quotation marks (`"`) are displayed as curly quotation marks (`“`). For the shortcodes to work correctly, you need straight quotation marks.

### Variation Product SKU Not Shown

In regards to the use of SKU shortcode like `[products skus="sku-name"]`, the variation product SKU isn’t intended to be displayed by itself, as opposed to the parent variable product SKU. Therefore it is expected that if we use an SKU from: **Product data > Variable product > Variations > Variation name > SKU**, it will not get displayed.

However, if we use an SKU from the parent variable product: **Product data > Variable product > Inventory > SKU**, it will get displayed.

![](https://woocommerce.com/wp-content/uploads/2021/05/sku-variable-parent1.png?w=950)

## Code Snippets

**Note:** We are unable to provide support for customizations under our [Support Policy](http://www.woocommerce.com/support-policy/). If you need to customize a snippet, or extend its functionality, seek assistance from a qualified WordPress/WooCommerce Developer. We highly recommend [Codeable](https://codeable.io/?ref=z4Hnp), or a [Certified WooExpert](https://woocommerce.com/experts/).

### Top-level Product Category List

The **Product Categories List block** can, as the name suggests, display a list of product categories. However, it doesn’t currently (April 2023) have the option to display only top-level categories and will display all categories, top-level and sub-categories alike.

This snippet adds a new shortcode `[top_level_product_categories_list]` that outputs a bulleted list of top-level product categories only. It can be added to a child theme’s functions.php file or via a code snippets plugin.

<?php

/\*

\* The shortcode is \[top\_level\_product\_categories\_list\]

\*/

add\_shortcode('top\_level\_product\_categories\_list', 'wc\_shortcode\_top\_level\_product\_categories\_list');

function wc\_shortcode\_top\_level\_product\_categories\_list() {

ob\_start();

$args = array(

'taxonomy' => 'product\_cat',

'parent' => 0

);

$product\_categories = get\_categories($args);

echo '<ul>';

foreach ($product\_categories as $category) {

echo '<li><a href="' . get\_term\_link($category\->slug, 'product\_cat') . '">' . $category\->name . '</a></li>';

}

echo '</ul>';

return ob\_get\_clean();

}
