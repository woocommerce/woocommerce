# Smoke Testing

We generally consider smoke testing using this definition [from Wikipedia](<https://href.li/?https://en.wikipedia.org/wiki/Smoke_testing_(software)>):

> **Smoke Testing** is a subset of test cases that cover the most important functionality of a component or system, used to aid assessment of whether the main functions of the software appear to work correctly. It is a set of tests run on each new build of a product to verify that the build is testable before the build is released into the hands of the test team

When testing builds the following things should be tested to ensure critical parts of the Blocks plugin are still functional.

## Setup

To make future testing more efficient, we recommend setting up some Blocks in advance so you can repeat tests on them whenever smoke testing.

### 1. Create a page with most blocks

<details><!-- markdownlint-disable-line no-inline-html -->
<summary>You can copy and paste (<kbd>Ctrl</kbd>+<kbd>Shift</kbd>+<kbd>V</kbd>) the following code into a new page to add all the blocks (click):</summary><!-- markdownlint-disable-line no-inline-html -->

Note: some blocks might fail to render because they are based on products having a specific id or depend on the site URL. You will need to remove and re-insert them.

```html
<!-- wp:woocommerce/featured-product {"editMode":false,"productId":15} -->
<!-- wp:button {"align":"center"} -->
<div class="wp-block-button aligncenter">
	<a
		class="wp-block-button__link"
		href=""
		>Shop now</a
	>
</div>
<!-- /wp:button -->
<!-- /wp:woocommerce/featured-product -->

<!-- wp:woocommerce/featured-category {"editMode":false,"categoryId":16} -->
<!-- wp:button {"align":"center"} -->
<div class="wp-block-button aligncenter">
	<a
		class="wp-block-button__link"
		href=""
		>Shop now</a
	>
</div>
<!-- /wp:button -->
<!-- /wp:woocommerce/featured-category -->

<!-- wp:woocommerce/handpicked-products {"editMode":false,"products":[15,32,16]} /-->

<!-- wp:woocommerce/product-best-sellers /-->

<!-- wp:woocommerce/product-top-rated /-->

<!-- wp:woocommerce/product-new /-->

<!-- wp:woocommerce/product-on-sale /-->

<!-- wp:woocommerce/product-category {"categories":[16]} /-->

<!-- wp:woocommerce/product-tag /-->

<!-- wp:woocommerce/products-by-attribute {"attributes":[{"id":22,"attr_slug":"pa_color"}],"editMode":false} /-->

<!-- wp:woocommerce/product-categories /-->

<!-- wp:woocommerce/product-categories {"isDropdown":true} /-->

<!-- wp:woocommerce/reviews-by-product {"editMode":false,"productId":15} -->
<div
	class="wp-block-woocommerce-reviews-by-product wc-block-reviews-by-product has-image has-name has-date has-rating has-content"
	data-image-type="reviewer"
	data-orderby="most-recent"
	data-reviews-on-page-load="10"
	data-reviews-on-load-more="10"
	data-show-load-more="true"
	data-show-orderby="true"
	data-product-id="15"
></div>
<!-- /wp:woocommerce/reviews-by-product -->

<!-- wp:woocommerce/reviews-by-category {"editMode":false,"categoryIds":[16]} -->
<div
	class="wp-block-woocommerce-reviews-by-category wc-block-reviews-by-category has-image has-name has-date has-rating has-content has-product-name"
	data-image-type="reviewer"
	data-orderby="most-recent"
	data-reviews-on-page-load="10"
	data-reviews-on-load-more="10"
	data-show-load-more="true"
	data-show-orderby="true"
	data-category-ids="16"
></div>
<!-- /wp:woocommerce/reviews-by-category -->

<!-- wp:woocommerce/all-reviews -->
<div
	class="wp-block-woocommerce-all-reviews wc-block-all-reviews has-image has-name has-date has-rating has-content has-product-name"
	data-image-type="reviewer"
	data-orderby="most-recent"
	data-reviews-on-page-load="10"
	data-reviews-on-load-more="10"
	data-show-load-more="true"
	data-show-orderby="true"
></div>
<!-- /wp:woocommerce/all-reviews -->

<!-- wp:search {"label":"Search","placeholder":"Search products…","buttonText":"Search","query":{"post_type":"product"}} /-->

<!-- wp:woocommerce/mini-cart /-->

<!-- wp:woocommerce/customer-account {"iconClass":"wc-block-customer-account__account-icon"} /-->

<!-- wp:woocommerce/all-products {"columns":3,"rows":3,"alignButtons":false,"contentVisibility":{"orderBy":true},"orderby":"date","layoutConfig":[["woocommerce/product-image",{"imageSizing":"cropped"}],["woocommerce/product-title"],["woocommerce/product-price"],["woocommerce/product-rating"],["woocommerce/product-button"]]} -->
<div class="wp-block-woocommerce-all-products wc-block-all-products" data-attributes="{&quot;alignButtons&quot;:false,&quot;columns&quot;:3,&quot;contentVisibility&quot;:{&quot;orderBy&quot;:true},&quot;isPreview&quot;:false,&quot;layoutConfig&quot;:[[&quot;woocommerce/product-image&quot;,{&quot;imageSizing&quot;:&quot;cropped&quot;}],[&quot;woocommerce/product-title&quot;],[&quot;woocommerce/product-price&quot;],[&quot;woocommerce/product-rating&quot;],[&quot;woocommerce/product-button&quot;]],&quot;orderby&quot;:&quot;date&quot;,&quot;rows&quot;:3}"></div>
<!-- /wp:woocommerce/all-products -->
```

</details>

### 2. Create a page with the Product Collection block, and filter blocks, setup to test that functionality in isolation. Using the columns block here too is a good idea to keep things organized

<details><!-- markdownlint-disable-line no-inline-html -->
<summary>You can copy and paste (<kbd>Ctrl</kbd>+<kbd>Shift</kbd>+<kbd>V</kbd>) the following code into a new page to add all the blocks (click):</summary><!-- markdownlint-disable-line no-inline-html -->

```html
<!-- wp:columns {"align":"wide"} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"width":"33.33%"} -->
<div class="wp-block-column" style="flex-basis:33.33%"><!-- wp:woocommerce/filter-wrapper {"filterType":"price-filter","heading":"Filter by price"} -->
<div class="wp-block-woocommerce-filter-wrapper"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Filter by price</h3>
<!-- /wp:heading -->

<!-- wp:woocommerce/price-filter {"heading":"","lock":{"remove":true}} -->
<div class="wp-block-woocommerce-price-filter is-loading"><span aria-hidden="true" class="wc-block-product-categories__placeholder"></span></div>
<!-- /wp:woocommerce/price-filter --></div>
<!-- /wp:woocommerce/filter-wrapper -->

<!-- wp:woocommerce/filter-wrapper {"filterType":"attribute-filter","heading":"Filter by attribute"} -->
<div class="wp-block-woocommerce-filter-wrapper"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Filter by attribute</h3>
<!-- /wp:heading -->

<!-- wp:woocommerce/attribute-filter {"attributeId":1,"showCounts":true,"queryType":"and","displayStyle":"dropdown","heading":"","lock":{"remove":true}} -->
<div class="wp-block-woocommerce-attribute-filter is-loading"></div>
<!-- /wp:woocommerce/attribute-filter --></div>
<!-- /wp:woocommerce/filter-wrapper -->

<!-- wp:woocommerce/filter-wrapper {"filterType":"stock-filter","heading":"Filter by stock status"} -->
<div class="wp-block-woocommerce-filter-wrapper"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Filter by stock status</h3>
<!-- /wp:heading -->

<!-- wp:woocommerce/stock-filter {"showCounts":true,"heading":"","lock":{"remove":true}} -->
<div class="wp-block-woocommerce-stock-filter is-loading"></div>
<!-- /wp:woocommerce/stock-filter --></div>
<!-- /wp:woocommerce/filter-wrapper -->

<!-- wp:woocommerce/filter-wrapper {"filterType":"rating-filter","heading":"Filter by rating"} -->
<div class="wp-block-woocommerce-filter-wrapper"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Filter by rating</h3>
<!-- /wp:heading -->

<!-- wp:woocommerce/rating-filter {"showCounts":true,"displayStyle":"dropdown","lock":{"remove":true}} -->
<div class="wp-block-woocommerce-rating-filter is-loading"></div>
<!-- /wp:woocommerce/rating-filter --></div>
<!-- /wp:woocommerce/filter-wrapper -->

<!-- wp:woocommerce/filter-wrapper {"filterType":"active-filters","heading":"Active filters"} -->
<div class="wp-block-woocommerce-filter-wrapper"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Active filters</h3>
<!-- /wp:heading -->

<!-- wp:woocommerce/active-filters {"heading":"","lock":{"remove":true}} -->
<div class="wp-block-woocommerce-active-filters is-loading"><span aria-hidden="true" class="wc-block-active-filters__placeholder"></span></div>
<!-- /wp:woocommerce/active-filters --></div>
<!-- /wp:woocommerce/filter-wrapper --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"66.66%"} -->
<div class="wp-block-column" style="flex-basis:66.66%"><!-- wp:woocommerce/product-collection {"query":{"perPage":9,"pages":0,"offset":0,"postType":"product","order":"asc","orderBy":"title"","search":"","exclude":[],"inherit":false,"taxQuery":{},"isProductCollectionBlock":true,"woocommerceOnSale":false,"woocommerceStockStatus":["instock","outofstock","onbackorder"],"woocommerceAttributes":[],"woocommerceHandPickedProducts":[]},"tagName":"div","displayLayout":{"type":"flex","columns":3}} -->
<div class="wp-block-woocommerce-product-collection"><!-- wp:woocommerce/product-template -->
<!-- wp:woocommerce/product-image {"imageSizing":"thumbnail","isDescendentOfQueryLoop":true} /-->

<!-- wp:post-title {"textAlign":"center","level":3,"isLink":true,"style":{"spacing":{"margin":{"bottom":"0.75rem","top":"0"}}},"fontSize":"medium","__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->

<!-- wp:woocommerce/product-price {"isDescendentOfQueryLoop":true,"textAlign":"center","fontSize":"small"} /-->

<!-- wp:woocommerce/product-button {"textAlign":"center","isDescendentOfQueryLoop":true,"fontSize":"small"} /-->
<!-- /wp:woocommerce/product-template -->

<!-- wp:query-pagination {"layout":{"type":"flex","justifyContent":"center"}} -->
<!-- wp:query-pagination-previous /-->

<!-- wp:query-pagination-numbers /-->

<!-- wp:query-pagination-next /-->
<!-- /wp:query-pagination -->

<!-- wp:query-no-results -->
<!-- wp:paragraph {"placeholder":"Add text or blocks that will display when a query returns no results."} -->
<p></p>
<!-- /wp:paragraph -->
<!-- /wp:query-no-results --></div>
<!-- /wp:woocommerce/product-collection --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
```

</details>

### 3. Add the Cart and Checkout block to the relevant templates

## Editor Tests

-   [ ] Ensure all WooCommerce Blocks are shown in the Block Inserter.
-   [ ] Check behaviour of Blocks added to a previous saved page from earlier plugin version
    -   [ ] Do they look correct?
    -   [ ] Ensure there are no block invalidation errors for blocks added to a page in a prior version.
    -   [ ] Can you change options/attributes in the Block inspector?
    -   [ ] Are changes persisted on save?
    -   [ ] Is the Browser error console free from errors/notices/warnings?
-   [ ] Test inserting various blocks into the editor
    -   [ ] This can be verified by copying and pasting the code examples above. However, please do also test manually inserting the next three blocks as representative examples for related blocks.
        -   [ ] All Products Blocks (this is powered by the Store API)
        -   [ ] Featured Product (this is powered by the REST API)
        -   [ ] On Sale Products (this is SSR)
    -   [ ] Is the Browser error console free from errors/notices/warnings after inserting them?
    -   [ ] Do they persist and continue to display correctly after save/refresh?

## Frontend Tests

-   [ ] Do the blocks on your pre-made pages render correctly?
-   [ ] Are the blocks with user facing interactions working as expected without errors in the browser console or user facing errors (such as All Products block and filter blocks).
-   [ ] Do critical flows for the Cart and Checkout blocks work?
    -   [ ] Address and shipping calculations
    -   [ ] Payment with core payment methods
    -   [ ] Payment with Stripe (extension) and saved payment methods
    -   [ ] Payment with Express payment methods (Chrome Pay or Apple Pay)
    -   [ ] Make sure you test with logged in user and in browser incognito mode.
