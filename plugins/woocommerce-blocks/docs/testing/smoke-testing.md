# Smoke Testing

We generally consider smoke testing using this definition [from Wikipedia](https://href.li/?https://en.wikipedia.org/wiki/Smoke_testing_(software)):

> **Smoke Testing** is a subset of test cases that cover the most important functionality of a component or system, used to aid assessment of whether the main functions of the software appear to work correctly. It is a set of tests run on each new build of a product to verify that the build is testable before the build is released into the hands of the test team

When testing builds the following things should be tested to ensure critical parts of the Blocks plugin are still functional.

## Setup

To make future testing more efficient, we recommend setting up some Blocks in advance so you can repeat tests on them whenever smoke testing.

### 1. Create a page with all regular and SSR blocks (such as the product grids) setup and configured.

<details>
<summary>You can copy and paste the following code into a new page to add all the blocks (click):</summary>

```html
<!-- wp:woocommerce/featured-product {"editMode":false,"productId":15} -->
<!-- wp:button {"align":"center"} -->
<div class="wp-block-button aligncenter"><a class="wp-block-button__link" href="https://ephemeral-aljullu-20200929.atomicsites.blog/product/beanie/">Shop now</a></div>
<!-- /wp:button -->
<!-- /wp:woocommerce/featured-product -->

<!-- wp:woocommerce/featured-category {"editMode":false,"categoryId":16} -->
<!-- wp:button {"align":"center"} -->
<div class="wp-block-button aligncenter"><a class="wp-block-button__link" href="https://ephemeral-aljullu-20200929.atomicsites.blog/product-category/clothing/">Shop now</a></div>
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
<div class="wp-block-woocommerce-reviews-by-product wc-block-reviews-by-product has-image has-name has-date has-rating has-content" data-image-type="reviewer" data-orderby="most-recent" data-reviews-on-page-load="10" data-reviews-on-load-more="10" data-show-load-more="true" data-show-orderby="true" data-product-id="15"></div>
<!-- /wp:woocommerce/reviews-by-product -->

<!-- wp:woocommerce/reviews-by-category {"editMode":false,"categoryIds":[16]} -->
<div class="wp-block-woocommerce-reviews-by-category wc-block-reviews-by-category has-image has-name has-date has-rating has-content has-product-name" data-image-type="reviewer" data-orderby="most-recent" data-reviews-on-page-load="10" data-reviews-on-load-more="10" data-show-load-more="true" data-show-orderby="true" data-category-ids="16"></div>
<!-- /wp:woocommerce/reviews-by-category -->

<!-- wp:woocommerce/all-reviews -->
<div class="wp-block-woocommerce-all-reviews wc-block-all-reviews has-image has-name has-date has-rating has-content has-product-name" data-image-type="reviewer" data-orderby="most-recent" data-reviews-on-page-load="10" data-reviews-on-load-more="10" data-show-load-more="true" data-show-orderby="true"></div>
<!-- /wp:woocommerce/all-reviews -->

<!-- wp:woocommerce/product-search {"formId":"wc-block-product-search-0"} -->
<div class="wp-block-woocommerce-product-search"><div class="wc-block-product-search"><form role="search" method="get" action="https://ephemeral-aljullu-20200929.atomicsites.blog/"><label for="wc-block-product-search-0" class="wc-block-product-search__label">Search</label><div class="wc-block-product-search__fields"><input type="search" id="wc-block-product-search-0" class="wc-block-product-search__field" placeholder="Search products…" name="s"/><input type="hidden" name="post_type" value="product"/><button type="submit" class="wc-block-product-search__button" label="Search"><svg aria-hidden="true" role="img" focusable="false" class="dashicon dashicons-arrow-right-alt2" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewbox="0 0 20 20"><path d="M6 15l5-5-5-5 1-2 7 7-7 7z"></path></svg></button></div></form></div></div>
<!-- /wp:woocommerce/product-search -->
```
</details>

In the `wp:woocommerce/product-search` substitute the URL used for the `action` attribute to your site URL or the block will not embedd correctly. 


### 2. Create a page with the All Products Block, and some Filter Blocks, setup to test that functionality in isolation. Using the columns block here too is a good idea to keep things organized.

<details>
<summary>You can copy and paste the following code into a new page to add all the blocks (click):</summary>

```html
<!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column {"width":33.33} -->
<div class="wp-block-column" style="flex-basis:33.33%"><!-- wp:woocommerce/price-filter -->
<div class="wp-block-woocommerce-price-filter is-loading" data-showinputfields="true" data-showfilterbutton="false" data-heading="Filter by price" data-heading-level="3"><span aria-hidden="true" class="wc-block-product-categories__placeholder"></span></div>
<!-- /wp:woocommerce/price-filter -->

<!-- wp:woocommerce/attribute-filter {"attributeId":1,"heading":"Filter by Color","displayStyle":"dropdown"} -->
<div class="wp-block-woocommerce-attribute-filter is-loading" data-attribute-id="1" data-show-counts="true" data-query-type="or" data-heading="Filter by Color" data-heading-level="3" data-display-style="dropdown"><span aria-hidden="true" class="wc-block-product-attribute-filter__placeholder"></span></div>
<!-- /wp:woocommerce/attribute-filter -->

<!-- wp:woocommerce/attribute-filter {"attributeId":2,"heading":"Filter by Size"} -->
<div class="wp-block-woocommerce-attribute-filter is-loading" data-attribute-id="2" data-show-counts="true" data-query-type="or" data-heading="Filter by Size" data-heading-level="3"><span aria-hidden="true" class="wc-block-product-attribute-filter__placeholder"></span></div>
<!-- /wp:woocommerce/attribute-filter -->

<!-- wp:woocommerce/active-filters -->
<div class="wp-block-woocommerce-active-filters is-loading" data-display-style="list" data-heading="Active filters" data-heading-level="3"><span aria-hidden="true" class="wc-block-active-product-filters__placeholder"></span></div>
<!-- /wp:woocommerce/active-filters --></div>
<!-- /wp:column -->

<!-- wp:column {"width":66.66} -->
<div class="wp-block-column" style="flex-basis:66.66%"><!-- wp:woocommerce/all-products {"columns":3,"rows":3,"alignButtons":false,"contentVisibility":{"orderBy":true},"orderby":"date","layoutConfig":[["woocommerce/product-image"],["woocommerce/product-title"],["woocommerce/product-price"],["woocommerce/product-rating"],["woocommerce/product-button"]]} -->
<div class="wp-block-woocommerce-all-products wc-block-all-products" data-attributes="{&quot;alignButtons&quot;:false,&quot;columns&quot;:3,&quot;contentVisibility&quot;:{&quot;orderBy&quot;:true},&quot;isPreview&quot;:false,&quot;layoutConfig&quot;:[[&quot;woocommerce/product-image&quot;],[&quot;woocommerce/product-title&quot;],[&quot;woocommerce/product-price&quot;],[&quot;woocommerce/product-rating&quot;],[&quot;woocommerce/product-button&quot;]],&quot;orderby&quot;:&quot;date&quot;,&quot;rows&quot;:3}"></div>
<!-- /wp:woocommerce/all-products --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
```

</details>


### 3. Add the Cart and Checkout block to the relevant WooCommerce pages.

## Editor Tests

* [ ] Ensure all WooCommerce Blocks are shown in the Block Inserter.
* [ ] Check behaviour of Blocks added to a previous saved page from earlier plugin version
    * [ ] Do they look correct?
    * [ ] Ensure there are no block invalidation errors for blocks added to a page in a prior version.
    * [ ] Can you change options/attributes in the Block inspector?
    * [ ] Are changes persisted on save?
    * [ ] Is the Browser error console free from errors/notices/warnings?
* [ ] Test inserting various blocks into the editor
    * [ ] This can be verified by copying and pasting the code examples above. However, please do also test manually inserting the next three blocks as representative examples for related blocks.
    * [ ] All Products Blocks (this is powered by the Store API)
    * [ ] Featured Product (this is powered by the REST API)
    * [ ] On Sale Products (this is SSR)
    * [ ] Is the Browser error console free from errors/notices/warnings after inserting them?
    * [ ] Do they persist and continue to display correctly after save/refresh?

## Frontend Tests

* [ ] Do the blocks on your pre-made pages render correctly?
* [ ] Are the blocks with user facing interactions working as expected without errors in the browser console or user facing errors (such as All Products block and filter blocks).
* [ ] Do critical flows for the Cart and Checkout blocks work?
  * [ ] Address and shipping calculations
  * [ ] Payment with core payment methods
  * [ ] Payment with Stripe (extension) and saved payment methods
  * [ ] Payment with Express payment methods (Chrome Pay or Apple Pay)
  * [ ] Make sure you test with logged in user and in browser incognito mode.

<!-- FEEDBACK -->
---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-gutenberg-products-block/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./docs/testing/smoke-testing.md)
<!-- /FEEDBACK -->

